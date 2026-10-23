<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) — Workzen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased bg-slate-50 dark:bg-slate-950"
      x-data="{
          sidebarOpen: $persist(true),
          mobileSidebar: false,
          darkMode: $persist(false),
          quickAdd: false,
          quickAddTitle: '',
          quickAddProject: '',
          quickAddType: 'task',
      }"
      x-init="
          document.documentElement.classList.toggle('dark', darkMode);
          $watch('darkMode', val => document.documentElement.classList.toggle('dark', val));
          document.addEventListener('keydown', e => {
              if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); quickAdd = true; }
              if (e.key === 'Escape') { quickAdd = false; mobileSidebar = false; }
          });
      ">

<div class="flex h-full overflow-hidden">

    <!-- ═══════════════════════════════════════════════════════
         MOBILE SIDEBAR OVERLAY
    ═══════════════════════════════════════════════════════ -->
    <div x-show="mobileSidebar"
         x-cloak
         @click="mobileSidebar = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="mobile-nav-overlay">
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════════════════════ -->
    <aside :class="[
               sidebarOpen ? 'w-[220px]' : 'w-[56px]',
               mobileSidebar ? 'translate-x-0 !w-[220px]' : '-translate-x-full lg:translate-x-0'
           ]"
           class="fixed inset-y-0 left-0 z-50 flex flex-col
                  bg-white dark:bg-[#0C0E13] text-slate-800 dark:text-white
                  transition-all duration-250 ease-in-out
                  border-r border-slate-200 dark:border-white/[0.05] lg:relative lg:inset-auto flex-shrink-0">

        <!-- ── Workspace Header ── -->
        <div class="flex items-center h-14 px-3 border-b border-slate-200 dark:border-white/[0.05] flex-shrink-0 gap-2.5">
            {{-- Logo icon: overflow-hidden crops to the W mark --}}
            <div class="w-8 h-8 flex-shrink-0 overflow-hidden rounded-xl"
                 style="background: url('{{ asset('images/workzen-logo.png') }}') left center / auto 100% no-repeat;"></div>
            <div x-show="sidebarOpen || mobileSidebar" x-transition.opacity.duration.150ms class="min-w-0 flex-1">
                {{-- Light mode: full logo image; dark mode: white text --}}
                <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen"
                     class="h-[18px] w-auto dark:hidden" style="max-width:120px;">
                <p class="hidden dark:block font-bold text-[13px] text-white/90 truncate leading-none tracking-tight">Workzen</p>
            </div>
            <!-- Collapse on desktop -->
            <button @click="sidebarOpen = !sidebarOpen"
                    x-show="sidebarOpen || mobileSidebar"
                    class="hidden lg:flex ml-auto w-6 h-6 items-center justify-center rounded-lg
                           text-slate-400 hover:text-slate-700 hover:bg-slate-100
                           dark:text-white/20 dark:hover:text-white/60 dark:hover:bg-white/[0.07]
                           transition-all duration-150 flex-shrink-0">
                <i class="bi bi-chevron-double-left text-[10px]"></i>
            </button>
        </div>

        <!-- ── Quick Add Button ── -->
        <div class="px-2.5 py-2.5 border-b border-slate-200 dark:border-white/[0.05] flex-shrink-0"
             x-show="sidebarOpen || mobileSidebar">
            <a href="{{ route('tasks.create') }}"
               class="flex items-center w-full justify-start px-3 gap-2.5 py-2 rounded-xl
                      bg-violet-50 hover:bg-violet-100 dark:bg-violet-600/15 dark:hover:bg-violet-600/25
                      border border-violet-200 hover:border-violet-300 dark:border-violet-500/25 dark:hover:border-violet-500/40
                      text-violet-600 hover:text-violet-700 dark:text-violet-300 dark:hover:text-violet-200
                      transition-all duration-150 text-[13px] font-semibold active:scale-[0.98]">
                <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                    <i class="bi bi-plus-lg text-sm"></i>
                </div>
                <span class="truncate">New Task</span>
                <kbd class="ml-auto text-[10px] font-mono text-violet-400/60 bg-violet-100 dark:bg-violet-900/30 px-1.5 py-0.5 rounded-md hidden sm:inline">⌘K</kbd>
            </a>
        </div>
        {{-- Collapsed: icon-only button opens quick-add modal --}}
        <div class="px-2.5 py-2.5 border-b border-slate-200 dark:border-white/[0.05] flex-shrink-0"
             x-show="!(sidebarOpen || mobileSidebar)" x-cloak>
            <button @click="quickAdd = true"
                    class="flex items-center w-full justify-center px-0 py-2 rounded-xl
                           bg-violet-50 hover:bg-violet-100 dark:bg-violet-600/15 dark:hover:bg-violet-600/25
                           border border-violet-200 dark:border-violet-500/25
                           text-violet-600 dark:text-violet-300 hover:text-violet-700 dark:hover:text-violet-200
                           transition-all duration-150 active:scale-[0.98]"
                    title="New Task (⌘K)">
                <i class="bi bi-plus-lg text-sm"></i>
            </button>
        </div>

        <!-- ── Nav ── -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2 space-y-0.5 scrollbar-hide"
             x-init="$nextTick(() => { const a = $el.querySelector('.sidebar-nav-active'); if (a) a.scrollIntoView({ block: 'nearest' }); })">

            @php
            $navGroups = [
                'MAIN' => [
                    ['route'=>'dashboard',      'icon'=>'bi-house-fill',        'label'=>'Home',         'match'=>'dashboard'],
                    ['route'=>'my-tasks',        'icon'=>'bi-check2-square',     'label'=>'My Tasks',     'match'=>'my-tasks'],
                    ['route'=>'projects.index',  'icon'=>'bi-folder2-open',      'label'=>'Projects',     'match'=>'projects.*'],
                ],
                'WORK' => [
                    ['route'=>'bugs.index',          'icon'=>'bi-bug',           'label'=>'Bug Tracker',  'match'=>'bugs.*'],
                    ['route'=>'daily-reports.index',  'icon'=>'bi-journal-text',  'label'=>'Daily Reports','match'=>'daily-reports.*'],
                    ['route'=>'time-entries.index',   'icon'=>'bi-stopwatch',     'label'=>'Time Tracker', 'match'=>'time-entries.*'],
                    ['route'=>'reports.index',        'icon'=>'bi-bar-chart-line','label'=>'Reports',      'match'=>'reports.*'],
                ],
                'PEOPLE' => [
                    ['route'=>'people.index',        'icon'=>'bi-people',         'label'=>'Directory',    'match'=>'people.*'],
                    ['route'=>'teams.index',          'icon'=>'bi-diagram-3',      'label'=>'Teams',        'match'=>'teams.*'],
                    ['route'=>'leave.index',          'icon'=>'bi-calendar-minus', 'label'=>'Leave',        'match'=>'leave.*', 'badge'=>'leave'],
                    ['route'=>'attendance.index',     'icon'=>'bi-calendar-check', 'label'=>'Attendance',   'match'=>'attendance.*'],
                    ['route'=>'announcements.index',  'icon'=>'bi-megaphone',      'label'=>'Announcements','match'=>'announcements.*'],
                ],
            ];
            $authUser = auth()->user();
            $navBadges = [
                'leave' => \App\Models\LeaveRequest::where('status', 'pending')
                    ->where('user_id', '!=', $authUser->id)
                    ->when(! ($authUser->hasRole('admin') || $authUser->can('leave.approve')),
                        fn ($q) => $q->whereIn('user_id', $authUser->directReports()->pluck('id')))
                    ->count(),
            ];
            $isAdmin = auth()->user()?->hasRole('admin');
            $me = auth()->user();
            $adminLinks = collect([
                ['route'=>'admin.dashboard',        'match'=>'admin.dashboard',    'icon'=>'bi-shield-check',  'label'=>'Admin Overview',   'show'=>$isAdmin],
                ['route'=>'users.index',            'match'=>'users.*',            'icon'=>'bi-person-gear',   'label'=>'Members',          'show'=>$isAdmin || $me->can('users.manage')],
                ['route'=>'admin.roles.index',      'match'=>'admin.roles.*',      'icon'=>'bi-key',           'label'=>'Roles & Perms',    'show'=>$isAdmin || $me->can('roles.manage')],
                ['route'=>'admin.attendance.index', 'match'=>'admin.attendance.*', 'icon'=>'bi-person-check',  'label'=>'Team Attendance',  'show'=>$isAdmin || $me->can('attendance.view') || $me->can('attendance.manage')],
                ['route'=>'admin.leave.index',      'match'=>'admin.leave.*',      'icon'=>'bi-calendar-range','label'=>'Leave & Holidays', 'show'=>$isAdmin || $me->can('leave.manage')],
                ['route'=>'admin.activity.index',   'match'=>'admin.activity.*',   'icon'=>'bi-activity',      'label'=>'Activity Log',     'show'=>$isAdmin || $me->can('activity.view')],
            ])->where('show', true);

            // Section label colors (light / dark)
            $sectionColors = [
                'MAIN'   => 'text-slate-400 dark:text-slate-300/70',
                'WORK'   => 'text-sky-500 dark:text-sky-300/70',
                'PEOPLE' => 'text-emerald-500 dark:text-emerald-300/70',
            ];
            @endphp

            @foreach($navGroups as $groupName => $items)
            <div class="{{ $loop->index > 0 ? 'mt-4' : 'mt-1' }}">
                <!-- Section label -->
                <div x-show="sidebarOpen || mobileSidebar"
                     x-transition.opacity.duration.100ms
                     class="px-2.5 mb-1 flex items-center gap-1.5">
                    <span class="text-[9px] font-bold tracking-[0.12em] uppercase {{ $sectionColors[$groupName] ?? 'text-slate-400' }}">{{ $groupName }}</span>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]"></div>
                </div>

                @foreach($items as $item)
                @php
                $active = request()->routeIs($item['match'] ?? $item['route']);
                @endphp
                <a href="{{ route($item['route']) }}"
                   @click.prevent="mobileSidebar = false; window.location.href='{{ route($item['route']) }}'"
                   class="sidebar-link relative flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium
                          transition-all duration-150 group select-none
                          {{ $active
                              ? 'bg-violet-100 dark:bg-violet-600/15 text-violet-700 dark:text-white sidebar-nav-active'
                              : 'text-slate-500 dark:text-white/40 hover:text-slate-900 dark:hover:text-white/80 hover:bg-slate-100 dark:hover:bg-white/[0.055]' }}"
                   :title="sidebarOpen || mobileSidebar ? '' : '{{ $item['label'] }}'">

                    @if($active)
                    <span class="absolute left-0 top-[18%] h-[64%] w-[3px] bg-violet-500 rounded-r-full"></span>
                    @endif

                    <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center rounded-lg transition-all duration-150
                                 {{ $active ? 'text-violet-600 dark:text-violet-400' : 'text-slate-400 dark:text-white/35 group-hover:text-slate-700 dark:group-hover:text-white/70' }}">
                        <i class="bi {{ $item['icon'] }} text-[14px]"></i>
                    </span>

                    <span x-show="sidebarOpen || mobileSidebar" class="flex-1 truncate leading-none">{{ $item['label'] }}</span>

                    @if(!empty($item['badge']) && ($navBadges[$item['badge']] ?? 0) > 0)
                    <span x-show="sidebarOpen || mobileSidebar"
                          class="ml-auto min-w-[18px] h-[18px] px-1.5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                        {{ $navBadges[$item['badge']] }}
                    </span>
                    <span x-show="!(sidebarOpen || mobileSidebar)"
                          class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                    @endif
                </a>
                @endforeach
            </div>
            @endforeach

            @if($adminLinks->isNotEmpty())
            <div class="mt-4">
                <div x-show="sidebarOpen || mobileSidebar" x-transition.opacity.duration.100ms
                     class="px-2.5 mb-1 flex items-center gap-1.5">
                    <span class="text-[9px] font-bold tracking-[0.12em] uppercase text-rose-400 dark:text-rose-300/50">ADMIN</span>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]"></div>
                </div>
                @foreach($adminLinks as $link)
                @php $on = request()->routeIs($link['match']); @endphp
                <a href="{{ route($link['route']) }}"
                   :title="sidebarOpen || mobileSidebar ? '' : '{{ $link['label'] }}'"
                   class="relative flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium
                          transition-all duration-150 group select-none
                          {{ $on
                              ? 'bg-rose-100 dark:bg-rose-500/10 text-rose-600 dark:text-rose-200 sidebar-nav-active'
                              : 'text-slate-500 dark:text-white/40 hover:text-slate-900 dark:hover:text-white/80 hover:bg-slate-100 dark:hover:bg-white/[0.055]' }}">
                    @if($on)
                    <span class="absolute left-0 top-[18%] h-[64%] w-[3px] bg-rose-500 rounded-r-full"></span>
                    @endif
                    <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center rounded-lg
                                 {{ $on ? 'text-rose-500 dark:text-rose-400' : 'text-slate-400 dark:text-white/35 group-hover:text-rose-500 dark:group-hover:text-rose-300/70' }}">
                        <i class="bi {{ $link['icon'] }} text-[14px]"></i>
                    </span>
                    <span x-show="sidebarOpen || mobileSidebar" class="flex-1 truncate leading-none">{{ $link['label'] }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </nav>

        <!-- ── Bottom ── -->
        <div class="px-2 pt-2 pb-3 border-t border-slate-200 dark:border-white/[0.05] flex-shrink-0 space-y-0.5">

            <!-- Quick daily report shortcut -->
            <a href="{{ route('daily-reports.create') }}"
               x-show="sidebarOpen || mobileSidebar"
               x-transition.opacity.duration.150ms
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-[12px] font-medium
                      bg-violet-50 hover:bg-violet-100 dark:bg-violet-500/8 dark:hover:bg-violet-500/15
                      border border-violet-200 hover:border-violet-300 dark:border-violet-500/15 dark:hover:border-violet-500/25
                      text-violet-600 hover:text-violet-700 dark:text-violet-300/70 dark:hover:text-violet-200
                      transition-all duration-150 mb-1">
                <i class="bi bi-journal-plus text-sm flex-shrink-0"></i>
                <span class="truncate">Submit Daily Report</span>
                <i class="bi bi-arrow-right-short ml-auto text-violet-400 dark:text-violet-400/40 flex-shrink-0"></i>
            </a>

            <a href="{{ route('settings.profile') }}"
               class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium
                      text-slate-500 dark:text-white/35 hover:text-slate-900 dark:hover:text-white/75
                      hover:bg-slate-100 dark:hover:bg-white/[0.055] transition-all duration-150"
               :title="sidebarOpen || mobileSidebar ? '' : 'Settings'">
                <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center">
                    <i class="bi bi-gear text-[14px]"></i>
                </span>
                <span x-show="sidebarOpen || mobileSidebar" class="truncate leading-none">Settings</span>
            </a>

            <a href="{{ route('help') }}"
               class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium
                      {{ request()->routeIs('help')
                          ? 'bg-slate-100 dark:bg-white/[0.09] text-slate-800 dark:text-white/80'
                          : 'text-slate-500 dark:text-white/35 hover:text-slate-900 dark:hover:text-white/75 hover:bg-slate-100 dark:hover:bg-white/[0.055]' }}
                      transition-all duration-150"
               :title="sidebarOpen || mobileSidebar ? '' : 'Help & Docs'">
                <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center">
                    <i class="bi bi-question-circle text-[14px]"></i>
                </span>
                <span x-show="sidebarOpen || mobileSidebar" class="truncate leading-none">Help & Docs</span>
            </a>

            <!-- Expand toggle (collapsed state) -->
            <button @click="sidebarOpen = !sidebarOpen"
                    x-show="!sidebarOpen && !mobileSidebar"
                    class="hidden lg:flex w-full items-center justify-center py-2 rounded-xl
                           text-slate-400 hover:text-slate-700 hover:bg-slate-100
                           dark:text-white/20 dark:hover:text-white/60 dark:hover:bg-white/[0.06]
                           transition-all duration-150"
                    title="Expand sidebar">
                <i class="bi bi-chevron-double-right text-xs"></i>
            </button>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">

        <!-- Header -->
        <header class="h-14 flex items-center gap-3 px-4 lg:px-5 flex-shrink-0
                       bg-white/90 dark:bg-slate-950/90 backdrop-blur-xl
                       border-b border-slate-200/60 dark:border-white/[0.06]
                       sticky top-0 z-30">

            <!-- Mobile menu button -->
            <button @click="mobileSidebar = !mobileSidebar"
                    class="lg:hidden btn-icon btn-ghost -ml-1">
                <i class="bi bi-list text-lg"></i>
            </button>

            <!-- Global search -->
            <form x-data="globalSearch()" @submit.prevent="go()" @click.away="open = false"
                  @keydown.window.slash="if (!['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.q.focus(); }"
                  action="{{ route('search') }}" class="relative flex-1 max-w-sm">
                <i class="bi absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-600 text-xs pointer-events-none"
                   :class="loading ? 'bi-arrow-repeat animate-spin' : 'bi-search'"></i>
                <input type="search" name="q" x-ref="q" x-model="q" @input="onInput()" autocomplete="off"
                       @focus="if (groups.length) open = true" @keydown.escape="open = false; $el.blur()"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                       placeholder="Search…  ( / )"
                       class="w-full pl-8 pr-3 py-2 text-sm rounded-xl
                              bg-slate-100/80 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100
                              border border-transparent focus:border-slate-300 dark:focus:border-slate-700
                              focus:bg-white dark:focus:bg-slate-800 focus:ring-0
                              placeholder-slate-400 dark:placeholder-slate-600
                              outline-none transition-all duration-150">
                <div x-show="open" x-cloak x-transition.opacity
                     class="dropdown-menu absolute left-0 right-0 top-full mt-2 max-h-[26rem] overflow-y-auto py-1">
                    <template x-if="!groups.length">
                        <p class="px-4 py-6 text-center text-xs text-slate-400">No matches</p>
                    </template>
                    <template x-for="g in groups" :key="g.label">
                        <div>
                            <p class="px-3.5 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="g.label"></p>
                            <template x-for="item in g.items" :key="item.url">
                                <a :href="item.url" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800"
                                   :class="flat[active] === item && 'bg-violet-50 dark:bg-violet-900/20'">
                                    <template x-if="item.avatar"><img :src="item.avatar" class="w-6 h-6 rounded-full" alt=""></template>
                                    <template x-if="item.color"><span class="w-2.5 h-2.5 rounded-full" :style="'background:' + item.color"></span></template>
                                    <span class="min-w-0">
                                        <span class="block text-sm text-slate-800 dark:text-slate-200 truncate" x-text="item.title"></span>
                                        <span class="block text-[11px] text-slate-400 truncate" x-text="item.subtitle"></span>
                                    </span>
                                </a>
                            </template>
                        </div>
                    </template>
                    <button type="submit" class="w-full text-left px-3.5 py-2 mt-1 border-t border-slate-100 dark:border-slate-800 text-xs text-violet-600 hover:bg-slate-50 dark:hover:bg-slate-800">
                        See all results for "<span x-text="q"></span>" →
                    </button>
                </div>
            </form>

            <!-- Header actions -->
            <div class="flex items-center gap-0.5 ml-auto">

                <!-- Quick add (mobile) -->
                <button @click="quickAdd = true"
                        class="lg:hidden btn-icon btn-ghost"
                        title="New Task">
                    <i class="bi bi-plus-lg text-base"></i>
                </button>

                <!-- Dark mode -->
                <button @click="darkMode = !darkMode"
                        class="btn-icon btn-ghost"
                        :title="darkMode ? 'Light mode' : 'Dark mode'">
                    <i :class="darkMode ? 'bi-sun-fill text-amber-400' : 'bi-moon-fill text-slate-400'" class="bi text-[15px]"></i>
                </button>

                <!-- Presence widget -->
                @php
                    $openShift = $me->openAttendance();
                    $workedToday = \App\Models\Attendance::where('user_id', $me->id)
                        ->whereDate('work_date', \App\Models\Attendance::localToday($me))
                        ->whereNotNull('clock_out_at')->get()->sum('duration_minutes');
                @endphp
                <div x-data="presenceWidget({{ Js::from([
                        'statuses'      => \App\Models\User::AVAILABILITY,
                        'availability'  => $me->availability ?: 'available',
                        'clockedIn'     => (bool) $openShift,
                        'clockInAt'     => $openShift?->clock_in_at?->toIso8601String(),
                        'workedMinutes' => $workedToday,
                    ]) }})" class="relative" @hd:close.window="open = false">
                    <button @click="toggleDropdown()" :disabled="busy"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-150
                                   text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                            :class="open ? 'bg-slate-100 dark:bg-slate-800' : ''">
                        <span class="w-2 h-2 rounded-full ring-2 ring-white dark:ring-slate-950 flex-shrink-0" :class="current.dot"></span>
                        <span class="hidden sm:inline truncate max-w-[80px]" x-text="current.label"></span>
                        <span class="hidden md:inline font-mono tabular-nums text-slate-400 text-[11px]" x-show="clockedIn" x-text="elapsed"></span>
                        <i class="bi bi-chevron-down text-[9px] text-slate-400"></i>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="dropdown-menu absolute right-0 top-full mt-2 w-64">
                        <div class="px-3.5 py-3 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Today's attendance</p>
                            <div class="flex items-center justify-between mt-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="clockedIn ? '🟢 Clocked in' : '⭕ Clocked out'"></p>
                                    <p class="text-xs text-slate-500 mt-0.5" x-show="clockedIn && clockInAt"
                                       x-text="'since ' + (clockInAt ? clockInAt.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'}) : '')"></p>
                                </div>
                                <span class="font-mono text-sm font-bold text-slate-800 dark:text-white tabular-nums" x-text="elapsed"></span>
                            </div>
                            <button @click="toggleClock()" :disabled="busy"
                                    class="mt-3 w-full btn-sm justify-center font-semibold"
                                    :class="clockedIn ? 'btn-secondary' : 'btn-primary'">
                                <i class="bi" :class="clockedIn ? 'bi-box-arrow-right' : 'bi-box-arrow-in-right'"></i>
                                <span x-text="clockedIn ? 'Clock out' : 'Clock in'"></span>
                            </button>
                        </div>
                        <div class="py-1">
                            <p class="px-3.5 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Set status</p>
                            <template x-for="(s, key) in statuses" :key="key">
                                <button @click="setStatus(key)" class="dropdown-item w-full">
                                    <span class="w-2 h-2 rounded-full" :class="s.dot"></span>
                                    <span class="flex-1 text-left text-sm" x-text="s.label"></span>
                                    <i class="bi bi-check2 text-violet-600 text-xs" x-show="availability === key"></i>
                                </button>
                            </template>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('attendance.index') }}" class="dropdown-item text-sm">
                            <i class="bi bi-calendar-check text-slate-400 text-sm"></i> My attendance history
                        </a>
                    </div>
                </div>

                <!-- Multi-timer widget -->
                <div x-data="multiTimerWidget()" x-init="init()" class="relative">
                    <!-- Trigger button -->
                    <button @click="open = !open"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-150 relative"
                            :class="hasActive
                                ? (runningCount > 0
                                    ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-800'
                                    : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800')
                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 dark:text-slate-400'"
                            :title="hasActive ? count + ' active timer(s)' : 'Track time'">
                        <i class="bi text-sm"
                           :class="hasActive
                               ? (runningCount > 0 ? 'bi-stopwatch-fill animate-pulse' : 'bi-pause-circle-fill')
                               : 'bi-stopwatch'"></i>
                        <span x-show="hasActive && runningCount > 0 && entries.length === 1"
                              x-text="entries[0]?.elapsed_formatted"
                              class="font-mono text-[11px] tabular-nums hidden sm:inline"
                              x-cloak></span>
                        <span x-show="hasActive && entries.length > 1"
                              x-text="count + ' timers'"
                              class="hidden sm:inline"
                              x-cloak></span>
                        <span x-show="!hasActive" class="hidden sm:block">Timer</span>
                        <!-- Active dot badge -->
                        <span x-show="hasActive" x-cloak
                              class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-slate-950"
                              :class="runningCount > 0 ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                    </button>

                    <!-- Dropdown panel -->
                    <div x-show="open" x-cloak
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="dropdown-menu absolute right-0 top-full mt-2 w-80 z-50">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">Active Timers</span>
                            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span x-show="runningCount > 0" class="text-emerald-600 dark:text-emerald-400">
                                    <i class="bi bi-play-circle-fill"></i> <span x-text="runningCount"></span>
                                </span>
                                <span x-show="pausedCount > 0" class="text-amber-500 dark:text-amber-400">
                                    <i class="bi bi-pause-circle-fill"></i> <span x-text="pausedCount"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div x-show="!hasActive" class="px-4 py-8 text-center">
                            <i class="bi bi-stopwatch text-2xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                            <p class="text-sm text-slate-500 dark:text-slate-400">No active timers</p>
                            <a href="{{ route('time-entries.index') }}" class="mt-3 inline-block text-xs text-violet-600 dark:text-violet-400 hover:underline">
                                Go to time tracking
                            </a>
                        </div>

                        <!-- Timer list -->
                        <div x-show="hasActive" class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="entry in entries" :key="entry.id">
                                <div class="px-4 py-3 flex items-center gap-3">
                                    <!-- Status dot -->
                                    <span class="w-2 h-2 rounded-full flex-shrink-0"
                                          :class="entry.status === 'running' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400'"></span>

                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 dark:text-white truncate" x-text="entry.task_title"></p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 truncate" x-text="entry.project_name"></p>
                                    </div>

                                    <!-- Elapsed -->
                                    <span class="font-mono text-xs tabular-nums flex-shrink-0"
                                          :class="entry.status === 'running' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'"
                                          x-text="entry.elapsed_formatted"></span>

                                    <!-- Controls -->
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <button x-show="entry.status === 'running'"
                                                @click.stop="pauseEntry(entry)"
                                                :disabled="entry.loading"
                                                class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition-colors disabled:opacity-40"
                                                title="Pause">
                                            <i class="bi bi-pause-fill text-xs"></i>
                                        </button>
                                        <button x-show="entry.status === 'paused'"
                                                @click.stop="resumeEntry(entry)"
                                                :disabled="entry.loading"
                                                class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors disabled:opacity-40"
                                                title="Resume">
                                            <i class="bi bi-play-fill text-xs"></i>
                                        </button>
                                        <button @click.stop="stopEntry(entry)"
                                                :disabled="entry.loading"
                                                class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors disabled:opacity-40"
                                                title="Stop">
                                            <i class="bi bi-stop-fill text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div x-show="hasActive"
                             class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <a href="{{ route('time-entries.index') }}"
                               class="text-xs text-violet-600 dark:text-violet-400 hover:underline">
                                View all time entries
                            </a>
                            <button @click="stopAll()"
                                    class="text-xs text-red-500 hover:text-red-600 dark:hover:text-red-400 hover:underline">
                                Stop all
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div x-data="notificationsDropdown()" x-init="init()" class="relative" @hd:close.window="open = false">
                    <button @click="toggle()"
                            class="btn-icon btn-ghost relative"
                            :class="open ? 'bg-slate-100 dark:bg-slate-800' : ''">
                        <i class="bi bi-bell text-[15px] text-slate-500 dark:text-slate-400"></i>
                        <span x-show="count > 0" x-cloak
                              class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 bg-rose-500 text-white
                                     text-[9px] font-bold rounded-full flex items-center justify-center px-1
                                     ring-2 ring-white dark:ring-slate-950"
                              x-text="count > 9 ? '9+' : count"></span>
                    </button>
                    <div x-show="open" x-cloak
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="dropdown-menu absolute right-0 top-full mt-2 w-80">
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                Notifications
                                <span x-show="count > 0" class="ml-1.5 text-xs font-normal text-violet-500"
                                      x-text="count + ' unread'"></span>
                            </span>
                            <div class="flex items-center gap-2">
                                <button x-show="count > 0" @click="markAllRead()"
                                        class="text-xs text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                                    Mark all read
                                </button>
                                <a href="{{ route('notifications.index') }}" class="text-xs text-violet-600 hover:text-violet-700 font-medium">View all</a>
                            </div>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <template x-if="items.length === 0">
                                <div class="py-10 text-center">
                                    <i class="bi bi-bell-slash text-2xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                                    <p class="text-xs text-slate-400">All caught up!</p>
                                </div>
                            </template>
                            <template x-for="n in items.slice(0,6)" :key="n.id">
                                <a :href="n.data.url || '#'"
                                   @click.prevent="markRead(n)"
                                   class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors border-b border-slate-50 dark:border-slate-800/60 last:border-b-0 cursor-pointer">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                         :class="{
                                             'bg-violet-100 dark:bg-violet-900/30': n.data.type === 'task_assigned',
                                             'bg-sky-100 dark:bg-sky-900/30': n.data.type === 'task_commented',
                                             'bg-emerald-100 dark:bg-emerald-900/30': n.data.type === 'task_status_changed',
                                             'bg-amber-100 dark:bg-amber-900/30': n.data.type === 'project_invite',
                                             'bg-slate-100 dark:bg-slate-800': !n.data.type,
                                         }">
                                        <i class="text-xs"
                                           :class="{
                                               'bi bi-person-plus text-violet-500': n.data.type === 'task_assigned',
                                               'bi bi-chat-left-text text-sky-500': n.data.type === 'task_commented',
                                               'bi bi-arrow-right-circle text-emerald-500': n.data.type === 'task_status_changed',
                                               'bi bi-folder-plus text-amber-500': n.data.type === 'project_invite',
                                               'bi bi-bell text-slate-400': !n.data.type,
                                           }"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs leading-snug"
                                           :class="n.read_at ? 'text-slate-500 dark:text-slate-400' : 'text-slate-800 dark:text-white font-medium'"
                                           x-text="n.data.message || 'New notification'"></p>
                                        <p class="text-[10px] text-slate-400 mt-1" x-text="timeAgo(n.created_at)"></p>
                                    </div>
                                    <div x-show="!n.read_at" class="w-2 h-2 rounded-full bg-violet-500 flex-shrink-0 mt-1.5"></div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- User menu -->
                <div x-data="{ open: false, toggle() { const was = this.open; window._hdClose(); if (!was) this.$nextTick(() => { this.open = true; }); } }"
                     class="relative" @hd:close.window="open = false">
                    <button @click="toggle()"
                            class="flex items-center gap-2 pl-2 pr-2.5 py-1.5 rounded-xl
                                   hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-150"
                            :class="open ? 'bg-slate-100 dark:bg-slate-800' : ''">
                        @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar_url }}"
                             class="w-7 h-7 rounded-full ring-2 ring-slate-200 dark:ring-slate-700 flex-shrink-0">
                        @else
                        <div class="w-7 h-7 rounded-full ring-2 ring-slate-200 dark:ring-slate-700
                                    bg-gradient-to-br from-violet-500 to-indigo-600
                                    flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', trim(auth()->user()->name))[1] ?? '', 0, 1)) }}
                        </div>
                        @endif
                        <div class="hidden md:block text-left">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white leading-none">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5 capitalize leading-none">{{ auth()->user()->getRoleNames()->first() ?? 'member' }}</p>
                        </div>
                        <i class="bi bi-chevron-down text-[9px] text-slate-400 hidden md:block"></i>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="dropdown-menu absolute right-0 top-full mt-2 w-52">
                        <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('settings.profile') }}" class="dropdown-item text-sm">
                                <i class="bi bi-person-circle text-slate-400 text-sm"></i> Profile settings
                            </a>
                            <a href="{{ route('daily-reports.create') }}" class="dropdown-item text-sm">
                                <i class="bi bi-journal-plus text-slate-400 text-sm"></i> Submit daily report
                            </a>
                            <a href="{{ route('attendance.index') }}" class="dropdown-item text-sm">
                                <i class="bi bi-clock text-slate-400 text-sm"></i> My attendance
                            </a>
                        </div>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item w-full text-rose-500 dark:text-rose-400 text-sm">
                                <i class="bi bi-box-arrow-right text-sm"></i> Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-cloak @unless(session('sticky')) x-init="setTimeout(() => show = false, 4000)" @endunless
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mx-4 lg:mx-5 mt-4 alert-success">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span class="flex-1">{{ session('success') }}</span>
            <button @click="show = false" class="text-emerald-600/60 hover:text-emerald-800 dark:hover:text-emerald-200">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        @endif
        @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-cloak x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mx-4 lg:mx-5 mt-4 alert-danger">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
            <span class="flex-1">{{ session('error') }}</span>
            <button @click="show = false" class="text-rose-600/60 hover:text-rose-800">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        @endif
        @if(session('info'))
        <div x-data="{ show: true }" x-show="show" x-cloak x-init="setTimeout(() => show = false, 4000)"
             class="mx-4 lg:mx-5 mt-4 alert-info">
            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
            <span>{{ session('info') }}</span>
        </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto">
            <div class="px-4 lg:px-5 py-5 max-w-screen-2xl mx-auto animate-fade-in">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     QUICK-ADD TASK MODAL  (⌘K)
═══════════════════════════════════════════════════════ -->
<div x-show="quickAdd" x-cloak class="quick-add-modal"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100">

    <!-- Backdrop -->
    <div class="quick-add-backdrop" @click="quickAdd = false"></div>

    <!-- Panel -->
    <div class="quick-add-panel"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0">

        <!-- Header bar -->
        <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-plus-lg text-violet-600 dark:text-violet-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold text-slate-900 dark:text-white">Quick Add Task</span>
            <kbd class="ml-auto text-[10px] font-mono text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">Esc</kbd>
        </div>

        <form method="POST" action="{{ route('tasks.quickStore') }}" class="p-4 space-y-3"
              x-on:submit="quickAdd = false">
            @csrf

            <!-- Title -->
            <input type="text" name="title" x-model="quickAddTitle"
                   placeholder="Task title…"
                   x-init="$nextTick(() => { if (quickAdd) $el.focus(); })"
                   x-effect="if (quickAdd) $nextTick(() => $el.focus())"
                   required
                   class="form-input text-base font-medium placeholder-slate-300 dark:placeholder-slate-600">

            <!-- Type pills -->
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach(['task'=>['bi-check-square-fill','text-slate-500','Task'],
                           'bug'=>['bi-bug-fill','text-red-500','Bug'],
                           'feature'=>['bi-stars','text-purple-500','Feature'],
                           'story'=>['bi-bookmark-fill','text-blue-500','Story'],
                           'epic'=>['bi-lightning-fill','text-orange-500','Epic']] as $tv => [$icon,$color,$label])
                <label class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-medium cursor-pointer transition-all duration-100
                               border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400
                               hover:border-violet-400 hover:text-violet-600"
                       :class="quickAddType === '{{ $tv }}' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300' : ''">
                    <input type="radio" name="type" value="{{ $tv }}" x-model="quickAddType" class="sr-only">
                    <i class="bi {{ $icon }} {{ $color }}"></i> {{ $label }}
                </label>
                @endforeach
            </div>

            <!-- Project + Priority -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select text-sm" required>
                        <option value="">Select project…</option>
                        @foreach(\App\Models\Project::where('status','!=','archived')
                                    ->orderBy('name')
                                    ->get() as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select text-sm">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 pt-1">
                <button type="submit"
                        class="btn-primary btn-sm flex-1 justify-center">
                    <i class="bi bi-check-lg"></i> Add Task
                </button>
                <button type="button" @click="quickAdd = false"
                        class="btn-secondary btn-sm px-4">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toasts -->
<div x-data="toastManager()" @toast.window="add($event.detail)"
     class="fixed bottom-5 right-5 z-[200] flex flex-col gap-2 pointer-events-none">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-4"
             class="pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-sm font-medium max-w-sm
                    bg-slate-900 dark:bg-white text-white dark:text-slate-900 border border-white/10 dark:border-slate-200">
            <i class="bi flex-shrink-0" :class="t.type === 'error' ? 'bi-exclamation-circle-fill text-rose-400' : t.type === 'warning' ? 'bi-exclamation-triangle-fill text-amber-400' : 'bi-check-circle-fill text-emerald-400'"></i>
            <span x-text="t.message"></span>
        </div>
    </template>
</div>

@stack('scripts')
</body>
</html>
