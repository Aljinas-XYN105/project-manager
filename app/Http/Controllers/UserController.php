<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ROLE_ORDER = ['admin','manager','product_owner','scrum_master','tech_lead','developer','designer','qa_engineer','devops_engineer','business_analyst','member'];

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role']);
        if ($request->filled('active')) {
            $filters['is_active'] = $request->boolean('active');
        }
        $users = $this->userRepository->all($filters);

        $counts = [
            'total'    => User::count(),
            'active'   => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'online'   => User::where('last_seen_at', '>=', now()->subMinutes(5))->count(),
        ];

        return view('users.index', ['users' => $users, 'roles' => $this->roles(), 'counts' => $counts]);
    }

    public function show(User $user)
    {
        $user->load(['teams', 'roles']);

        $stats = [
            'assigned_tasks'   => $user->assignedTasks()->whereNull('parent_id')->count(),
            'completed_tasks'  => $user->assignedTasks()->where('status', 'done')->count(),
            'hours_logged'     => round(
                TimeEntry::where('user_id', $user->id)->whereNotNull('stopped_at')->sum('duration_seconds') / 3600, 1
            ),
            'projects_count'   => $user->projects()->count(),
        ];

        $recentTasks = $user->assignedTasks()
            ->with('project')
            ->whereNull('parent_id')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('users.show', compact('user', 'stats', 'recentTasks'));
    }

    public function create()
    {
        return view('users.form', [
            'user'  => new User(['is_active' => true, 'timezone' => auth()->user()->timezone ?: config('app.timezone')]),
            'roles' => $this->roles(),
            'teams' => Team::orderBy('name')->get(),
        ] + $this->formLookups());
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', Password::min(8)],
        ]);

        $password = $data['password'] ?: Str::password(12, symbols: false);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($password),
            'job_title'         => $data['job_title'] ?? null,
            'team_id'           => $data['team_id'] ?? null,
            'timezone'          => $data['timezone'] ?? config('app.timezone'),
            'is_active'         => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ] + $this->hrFields($data));
        $user->syncRoles($data['roles']);

        $message = "Member \"{$user->name}\" created.";
        if (! $data['password']) {
            $message .= " Temporary password: {$password} — share it securely; it won't be shown again.";
        }

        return redirect()->route('users.index')->with('success', $message)->with('sticky', ! $data['password']);
    }

    public function edit(User $user)
    {
        return view('users.form', [
            'user'  => $user->load('roles'),
            'roles' => $this->roles(),
            'teams' => Team::orderBy('name')->get(),
        ] + $this->formLookups($user));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user) + [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $isSelf = $user->is($request->user());
        if ($isSelf && $user->hasRole('admin') && ! in_array('admin', $data['roles'])) {
            return back()->withInput()->with('error', 'You cannot remove the admin role from your own account.');
        }

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'team_id'   => $data['team_id'] ?? null,
            'timezone'  => $data['timezone'] ?? $user->timezone,
            'is_active' => $isSelf ? true : $request->boolean('is_active'),
        ] + $this->hrFields($data));
        $user->syncRoles($data['roles']);

        return redirect()->route('users.index')->with('success', "Member \"{$user->name}\" updated.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate(['password' => ['nullable', Password::min(8)]]);
        $password = $data['password'] ?: Str::password(12, symbols: false);

        $user->update(['password' => Hash::make($password)]);

        $message = "Password for {$user->name} was reset.";
        if (! $data['password']) {
            $message .= " New temporary password: {$password}";
        }

        return back()->with('success', $message)->with('sticky', ! $data['password']);
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "{$user->name} {$status}.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->update(['is_active' => false]);
        $user->delete();

        return redirect()->route('users.index')->with('success', "{$user->name} was removed.");
    }

    private function formLookups(?User $user = null): array
    {
        return [
            'managers'    => User::active()->when($user, fn ($q) => $q->where('id', '!=', $user->id))->orderBy('name')->get(['id', 'name', 'job_title']),
            'departments' => User::whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
        ];
    }

    private function hrFields(array $data): array
    {
        $fields = collect($data)->only([
            'employee_code', 'department', 'manager_id', 'joined_on', 'date_of_birth',
            'phone', 'emergency_contact_name', 'emergency_contact_phone',
        ])->map(fn ($v) => $v === '' ? null : $v)->all();

        return $fields + ['employment_type' => $data['employment_type'] ?? 'full_time'];
    }

    private function rules(?User $user = null): array
    {
        return [
            'employee_code'           => ['nullable', 'string', 'max:30', Rule::unique('users', 'employee_code')->ignore($user?->id)],
            'department'              => ['nullable', 'string', 'max:80'],
            'employment_type'         => ['nullable', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'manager_id'              => ['nullable', 'exists:users,id', Rule::notIn(array_filter([$user?->id])),
                function ($attr, $value, $fail) use ($user) {
                    // Walk up the chosen manager's chain; finding this user there would create a loop.
                    $seen = [];
                    for ($m = User::find($value); $user && $m && ! in_array($m->id, $seen); $m = $m->manager) {
                        if ($m->manager_id === $user->id) {
                            return $fail("{$m->name} already reports (directly or indirectly) to {$user->name}.");
                        }
                        $seen[] = $m->id;
                    }
                }],
            'joined_on'               => ['nullable', 'date'],
            'date_of_birth'           => ['nullable', 'date', 'before:today'],
            'phone'                   => ['nullable', 'string', 'max:40'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'name'      => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'team_id'   => ['nullable', 'exists:teams,id'],
            'timezone'  => ['nullable', 'timezone'],
            'roles'     => ['required', 'array', 'min:1'],
            'roles.*'   => ['string', 'exists:roles,name'],
        ];
    }

    private function roles()
    {
        return Role::withCount('users')->get()->sortBy(
            fn ($r) => array_search($r->name, self::ROLE_ORDER) !== false ? array_search($r->name, self::ROLE_ORDER) : 99
        )->values();
    }
}
