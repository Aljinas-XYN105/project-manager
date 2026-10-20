<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'job_title',
        'timezone', 'theme', 'team_id', 'last_seen_at', 'is_active',
        'availability', 'availability_note',
        'employee_code', 'department', 'employment_type', 'manager_id', 'joined_on', 'date_of_birth',
        'phone', 'emergency_contact_name', 'emergency_contact_phone',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contract'  => 'Contract',
        'intern'    => 'Intern',
    ];

    public const AVAILABILITY = [
        'available' => ['label' => 'Available',  'dot' => 'bg-emerald-500', 'icon' => 'bi-check-circle-fill'],
        'busy'      => ['label' => 'Busy',       'dot' => 'bg-rose-500',    'icon' => 'bi-dash-circle-fill'],
        'meeting'   => ['label' => 'In a meeting', 'dot' => 'bg-violet-500', 'icon' => 'bi-camera-video-fill'],
        'away'      => ['label' => 'Away',       'dot' => 'bg-amber-500',   'icon' => 'bi-moon-fill'],
        'offline'   => ['label' => 'Offline',    'dot' => 'bg-slate-400',   'icon' => 'bi-circle'],
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_seen_at'      => 'datetime',
            'is_active'         => 'boolean',
            'joined_on'         => 'date',
            'date_of_birth'     => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role', 'joined_at')->withTimestamps();
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role')->withTimestamps();
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees')
            ->withPivot('assigned_by')->withTimestamps();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reportSnapshots(): HasMany
    {
        return $this->hasMany(ReportSnapshot::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class)->orderByDesc('report_date');
    }

    public function todayReport(): ?DailyReport
    {
        return $this->dailyReports()->where('report_date', today())->first();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** Days used (approved) and pending for each leave type in a calendar year. */
    public function leaveUsage(int $year): \Illuminate\Support\Collection
    {
        return $this->leaveRequests()
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', $year)
            ->get()
            ->groupBy('leave_type_id')
            ->map(fn ($reqs) => [
                'approved' => (float) $reqs->where('status', 'approved')->sum('days'),
                'pending'  => (float) $reqs->where('status', 'pending')->sum('days'),
            ]);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function openAttendance(): ?Attendance
    {
        return $this->attendances()->whereNull('clock_out_at')->latest('clock_in_at')->first();
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    // Offline once the session goes idle, regardless of the last chosen status.
    public function getEffectiveAvailabilityAttribute(): string
    {
        return $this->is_online ? ($this->availability ?: 'available') : 'offline';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn($q) => $q->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%"));
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=7F9CF5&background=EBF4FF&bold=true";
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        return implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), array_slice($words, 0, 2)));
    }
}
