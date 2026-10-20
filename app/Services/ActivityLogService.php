<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public function log(
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = [],
        string $logName = 'default'
    ): ActivityLog {
        return ActivityLog::create([
            'log_name'     => $logName,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'causer_type'  => $causer ? get_class($causer) : null,
            'causer_id'    => $causer?->getKey(),
            'properties'   => $properties,
        ]);
    }

    public function getLogsForSubject(Model $subject, int $limit = 50): Collection
    {
        return ActivityLog::with('causer')
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey())
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getRecentLogs(int $limit = 20): Collection
    {
        return ActivityLog::with('causer')
            ->where('description', '!=', 'failed login attempt')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
