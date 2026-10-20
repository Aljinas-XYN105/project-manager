<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public const CATEGORIES = [
        'auth'    => 'Logins & sessions',
        'audit'   => 'Actions',
        'default' => 'Work activity',
    ];

    public function index(Request $request)
    {
        $logs = $this->query($request)->paginate(50)->withQueryString();

        $users = User::withTrashed()->orderBy('name')->get(['id', 'name']);

        $stats = [
            'today'         => ActivityLog::whereDate('created_at', today())->count(),
            'logins_today'  => ActivityLog::where('log_name', 'auth')->where('description', 'logged in')->whereDate('created_at', today())->count(),
            'failed_today'  => ActivityLog::where('log_name', 'auth')->where('description', 'failed login attempt')->whereDate('created_at', today())->count(),
            'active_users'  => ActivityLog::whereDate('created_at', today())->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
        ];

        return view('admin.activity.index', [
            'logs'       => $logs,
            'users'      => $users,
            'stats'      => $stats,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($request);
        $tz    = $request->user()->timezone ?: config('app.timezone');

        return response()->streamDownload(function () use ($query, $tz) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ["Time ({$tz})", 'User', 'Email', 'Category', 'Action', 'IP', 'Method', 'URL']);
            $query->chunk(500, function ($logs) use ($out, $tz) {
                foreach ($logs as $log) {
                    $p = $log->properties ?? [];
                    fputcsv($out, [
                        $log->created_at->copy()->setTimezone($tz)->toDateTimeString(),
                        $log->causer?->name ?? 'System / guest',
                        $log->causer?->email ?? ($p['email'] ?? ''),
                        self::CATEGORIES[$log->log_name] ?? $log->log_name,
                        $log->description,
                        $p['ip'] ?? '',
                        $p['method'] ?? '',
                        $p['url'] ?? '',
                    ]);
                }
            });
            fclose($out);
        }, 'activity-log-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request)
    {
        return ActivityLog::with(['causer' => fn ($q) => $q->withTrashed()])
            ->when($request->filled('user_id'), fn ($q) => $q->where('causer_type', User::class)->where('causer_id', $request->integer('user_id')))
            ->when($request->filled('category'), fn ($q) => $q->where('log_name', $request->input('category')))
            ->when($request->filled('q'), fn ($q) => $q->where('description', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id');
    }
}
