@php
$me = auth()->user();
$adminTabs = collect([
    ['route' => 'admin.dashboard',        'match' => 'admin.dashboard',    'icon' => 'bi-speedometer2',   'label' => 'Overview',    'show' => $me->hasRole('admin')],
    ['route' => 'users.index',            'match' => 'users.*',            'icon' => 'bi-people-fill',    'label' => 'Members',     'show' => $me->hasRole('admin') || $me->can('users.manage')],
    ['route' => 'admin.roles.index',      'match' => 'admin.roles.*',      'icon' => 'bi-key-fill',       'label' => 'Roles & Permissions', 'show' => $me->hasRole('admin') || $me->can('roles.manage')],
    ['route' => 'admin.attendance.index', 'match' => 'admin.attendance.*', 'icon' => 'bi-clock-history',  'label' => 'Attendance',  'show' => $me->hasRole('admin') || $me->can('attendance.view') || $me->can('attendance.manage')],
    ['route' => 'admin.leave.index',      'match' => 'admin.leave.*',      'icon' => 'bi-calendar2-range', 'label' => 'Leave & Holidays', 'show' => $me->hasRole('admin') || $me->can('leave.manage')],
    ['route' => 'admin.activity.index',   'match' => 'admin.activity.*',   'icon' => 'bi-activity',       'label' => 'Activity Log', 'show' => $me->hasRole('admin') || $me->can('activity.view')],
])->where('show', true);
@endphp
@if($adminTabs->count() > 1)
<div class="mb-6 overflow-x-auto -mx-1 px-1">
    <nav class="tab-bar inline-flex min-w-max">
        @foreach($adminTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="tab-item inline-flex items-center gap-1.5 {{ request()->routeIs($tab['match']) ? 'active' : '' }}">
            <i class="bi {{ $tab['icon'] }} text-xs"></i> {{ $tab['label'] }}
        </a>
        @endforeach
    </nav>
</div>
@endif
