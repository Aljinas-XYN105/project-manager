@extends('layouts.app')
@section('title', 'Help & Documentation')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ activeSection: 'overview' }">

    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                    <i class="bi bi-question-circle-fill text-sky-600 dark:text-sky-400"></i>
                </span>
                Help & Documentation
            </h1>
            <p class="text-sm text-slate-500 mt-1">Everything you need to know about Workzen — no onboarding required.</p>
        </div>
    </div>

    <div class="flex gap-6 items-start">

        {{-- Sticky nav --}}
        <aside class="hidden lg:block w-56 flex-shrink-0 sticky top-24">
            <nav class="card p-2 space-y-0.5">
                @php
                $sections = [
                    'overview'       => ['icon' => 'bi-house-fill',        'label' => 'Overview'],
                    'projects'       => ['icon' => 'bi-folder2-open',      'label' => 'Projects'],
                    'tasks'          => ['icon' => 'bi-list-task',         'label' => 'Tasks'],
                    'kanban'         => ['icon' => 'bi-kanban',            'label' => 'Kanban Board'],
                    'sprints'        => ['icon' => 'bi-speedometer2',      'label' => 'Sprints'],
                    'bugs'           => ['icon' => 'bi-bug-fill',          'label' => 'Bug Tracker'],
                    'daily-reports'  => ['icon' => 'bi-journal-text',      'label' => 'Daily Reports'],
                    'time-tracking'  => ['icon' => 'bi-clock-history',     'label' => 'Time Tracking'],
                    'teams'          => ['icon' => 'bi-people-fill',       'label' => 'Teams'],
                    'my-tasks'       => ['icon' => 'bi-check2-square',     'label' => 'My Tasks & Search'],
                    'people'         => ['icon' => 'bi-person-lines-fill', 'label' => 'People & Org Chart'],
                    'leave'          => ['icon' => 'bi-airplane',          'label' => 'Leave & Holidays'],
                    'attendance'     => ['icon' => 'bi-calendar-check',    'label' => 'Attendance & Status'],
                    'announcements'  => ['icon' => 'bi-megaphone',         'label' => 'Announcements'],
                    'admin'          => ['icon' => 'bi-shield-fill-check', 'label' => 'Admin Panel'],
                    'roles'          => ['icon' => 'bi-person-badge-fill', 'label' => 'Roles & Permissions'],
                    'tips'           => ['icon' => 'bi-lightbulb-fill',    'label' => 'Tips & Shortcuts'],
                ];
                @endphp
                @foreach($sections as $id => $s)
                <button @click="activeSection = '{{ $id }}'"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-left transition-all"
                        :class="activeSection === '{{ $id }}'
                            ? 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400 font-medium'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'">
                    <i class="bi {{ $s['icon'] }} text-sm flex-shrink-0"></i>
                    {{ $s['label'] }}
                </button>
                @endforeach
            </nav>
        </aside>

        {{-- Content --}}
        <div class="flex-1 min-w-0 space-y-5">

            {{-- Mobile section picker --}}
            <div class="lg:hidden card p-3">
                <select class="form-select w-full" @change="activeSection = $event.target.value">
                    @foreach($sections as $id => $s)
                    <option value="{{ $id }}">{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ── OVERVIEW ── --}}
            <div x-show="activeSection === 'overview'" x-transition>
                <div class="card p-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                        <i class="bi bi-house-fill text-violet-500"></i> Workzen Overview
                    </h2>
                    <p class="text-slate-500 text-sm mb-5">Workzen combines project management for development teams with everyday HR: attendance, leave, people and announcements.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                        @foreach([
                            ['icon'=>'bi-folder2-open','color'=>'violet','title'=>'Projects','desc'=>'Organize work into projects, each with its own board, sprints, and team.'],
                            ['icon'=>'bi-list-task','color'=>'blue','title'=>'Tasks & My Tasks','desc'=>'Track stories, bugs and chores; see everything assigned to you in one list.'],
                            ['icon'=>'bi-kanban','color'=>'emerald','title'=>'Kanban & Sprints','desc'=>'Drag tasks across columns and time-box work into sprints.'],
                            ['icon'=>'bi-bug-fill','color'=>'rose','title'=>'Bug Tracker','desc'=>'Log and triage bugs with severity, priority, and assignee.'],
                            ['icon'=>'bi-journal-text','color'=>'amber','title'=>'Daily Reports','desc'=>'Submit standups with hours per task, blockers, and new tasks on the fly.'],
                            ['icon'=>'bi-clock-history','color'=>'blue','title'=>'Time & Attendance','desc'=>'Task timers, automatic sign-in/out times, and your availability status.'],
                            ['icon'=>'bi-airplane','color'=>'violet','title'=>'Leave & Holidays','desc'=>'Request time off, track balances, and see who is away on the team calendar.'],
                            ['icon'=>'bi-person-lines-fill','color'=>'emerald','title'=>'People & Announcements','desc'=>'Staff directory, org chart, birthdays, and company news.'],
                            ['icon'=>'bi-shield-fill-check','color'=>'rose','title'=>'Admin','desc'=>'Members, roles, attendance, leave setup, activity log and performance metrics.'],
                        ] as $feature)
                        <div class="flex gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <div class="w-9 h-9 rounded-lg bg-{{ $feature['color'] }}-100 dark:bg-{{ $feature['color'] }}-900/30 flex items-center justify-center flex-shrink-0">
                                <i class="bi {{ $feature['icon'] }} text-{{ $feature['color'] }}-600 dark:text-{{ $feature['color'] }}-400"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-sm text-slate-900 dark:text-white">{{ $feature['title'] }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="bg-violet-50 dark:bg-violet-900/10 rounded-xl p-4 border border-violet-100 dark:border-violet-900">
                        <p class="font-semibold text-violet-800 dark:text-violet-300 text-sm mb-2">Quick Start</p>
                        <ol class="space-y-1.5 text-sm text-violet-700 dark:text-violet-400">
                            <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-violet-200 dark:bg-violet-800 text-violet-700 dark:text-violet-300 text-xs flex items-center justify-center flex-shrink-0 font-bold mt-px">1</span> Create a project and invite your team</li>
                            <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-violet-200 dark:bg-violet-800 text-violet-700 dark:text-violet-300 text-xs flex items-center justify-center flex-shrink-0 font-bold mt-px">2</span> Add tasks to the backlog</li>
                            <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-violet-200 dark:bg-violet-800 text-violet-700 dark:text-violet-300 text-xs flex items-center justify-center flex-shrink-0 font-bold mt-px">3</span> Create a sprint and pull in tasks</li>
                            <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-violet-200 dark:bg-violet-800 text-violet-700 dark:text-violet-300 text-xs flex items-center justify-center flex-shrink-0 font-bold mt-px">4</span> Start the sprint and use the kanban board daily</li>
                            <li class="flex items-start gap-2"><span class="w-5 h-5 rounded-full bg-violet-200 dark:bg-violet-800 text-violet-700 dark:text-violet-300 text-xs flex items-center justify-center flex-shrink-0 font-bold mt-px">5</span> Submit your daily standup each day, and request leave from the Leave page when you need time off</li>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- ── PROJECTS ── --}}
            <div x-show="activeSection === 'projects'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-folder2-open text-violet-500"></i> Projects
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Projects are the top-level container for all your work. Each project has its own tasks, kanban board, sprints, bugs, and members.
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I create a project?', 'a' => 'Click "New Project" from the sidebar or Projects page. Fill in the name, description, choose a color, set start/end dates, and assign team members. The project will appear in your sidebar immediately.'],
                            ['q' => 'What is the project status?', 'a' => 'Projects have statuses: Planning (not started), Active (in progress), On Hold (paused), Completed (finished), or Cancelled. You can update this from the project settings.'],
                            ['q' => 'How do I add team members?', 'a' => 'Open the project, go to its Settings tab, and use the Members section to add users. You can assign them as a Manager or Member role within the project.'],
                            ['q' => 'What are project colors for?', 'a' => 'Each project has a color accent used across the dashboard, progress bars, and breadcrumbs to visually distinguish projects at a glance.'],
                            ['q' => 'Can I archive a project?', 'a' => 'Yes — use the ⋮ menu on the Projects list and choose Archive. Archived projects are hidden from the main list but all data is preserved.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── TASKS ── --}}
            <div x-show="activeSection === 'tasks'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-list-task text-blue-500"></i> Tasks
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Tasks are the unit of work in Workzen. They belong to a project and can be of different types, assigned to team members, and tracked across statuses.
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-2">
                        @foreach([
                            ['icon'=>'bi-lightning-charge-fill','color'=>'text-violet-500','type'=>'Story','desc'=>'Feature / user story'],
                            ['icon'=>'bi-bug-fill','color'=>'text-rose-500','type'=>'Bug','desc'=>'Something broken'],
                            ['icon'=>'bi-tools','color'=>'text-amber-500','type'=>'Chore','desc'=>'Technical work'],
                            ['icon'=>'bi-file-earmark-text','color'=>'text-blue-500','type'=>'Task','desc'=>'General task'],
                            ['icon'=>'bi-graph-up-arrow','color'=>'text-emerald-500','type'=>'Epic','desc'=>'Large body of work'],
                            ['icon'=>'bi-question-circle','color'=>'text-slate-400','type'=>'Spike','desc'=>'Research / exploration'],
                        ] as $t)
                        <div class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <i class="bi {{ $t['icon'] }} {{ $t['color'] }} text-sm"></i>
                            <div>
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $t['type'] }}</p>
                                <p class="text-[10px] text-slate-400">{{ $t['desc'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @include('help._section', [
                        'items' => [
                            ['q' => 'What views are available for tasks?', 'a' => 'Tasks have four views accessible via the tab bar: List (table), Board (kanban), Calendar (timeline), and Backlog (unassigned to a sprint). Switch between them freely.'],
                            ['q' => 'What do the statuses mean?', 'a' => 'To Do → not started. In Progress → being worked on. In Review → waiting on review. Done → completed. Cancelled → abandoned. Blocked → stuck on a dependency.'],
                            ['q' => 'How do priorities work?', 'a' => 'Priority levels are: Critical (red), High (orange), Medium (amber), Low (blue). They help the team see what to work on first. Set them when creating or editing a task.'],
                            ['q' => 'What is a ticket key?', 'a' => 'Each task gets an auto-generated ticket key like PROJ-42. Use it in commits, PR titles, or messages to reference a task uniquely across projects.'],
                            ['q' => 'Can I change task status inline?', 'a' => 'Yes — on the task list view, click the status badge in any row to get a dropdown. The change saves instantly via the API with no page reload.'],
                            ['q' => 'How do I do bulk actions?', 'a' => 'Check the boxes on the left of tasks in List view. A floating action bar appears at the bottom letting you change status or delete multiple tasks at once.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── KANBAN ── --}}
            <div x-show="activeSection === 'kanban'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-kanban text-emerald-500"></i> Kanban Board
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        The Kanban board gives a visual overview of the project's task flow. Tasks are in columns that represent stages of completion.
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I move a task to a different column?', 'a' => 'Drag and drop the task card onto any column. The column highlights with a violet border when it is the active drop target. The status is saved automatically.'],
                            ['q' => 'What is WIP limit?', 'a' => 'Work In Progress (WIP) limits cap how many tasks can be in a column at once. When the limit is reached the column header turns red. Set limits in Board Settings (gear icon, top right).'],
                            ['q' => 'How do I add a new column?', 'a' => 'Click the gear icon → Board Settings → Add New Column. Give it a name and color. Columns represent your team\'s stages (e.g., To Do, In Progress, In Review, Done).'],
                            ['q' => 'Can I add a task directly from the board?', 'a' => 'Yes — at the bottom of each column is an "+ Add task" button. Click it, type a title, and press Enter. The task is created in that column instantly.'],
                            ['q' => 'What is the quick add feature?', 'a' => 'Quick add creates a minimal task with just a title. For full details (description, priority, assignee, due date), open the task and fill in the extra fields.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── SPRINTS ── --}}
            <div x-show="activeSection === 'sprints'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-speedometer2 text-amber-500"></i> Sprints
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Sprints are time-boxed iterations (usually 1–2 weeks) during which the team commits to completing a defined set of tasks. They help teams work in predictable, focused cycles.
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I create a sprint?', 'a' => 'Navigate to a project → Sprints tab → New Sprint. Give it a name, a goal, and start/end dates. After creating it, add tasks from the backlog.'],
                            ['q' => 'What is the sprint lifecycle?', 'a' => 'Planning → the sprint is created but not started. Active → the team is working on it (only one sprint can be active per project at a time). Completed → the sprint is finished and tasks that are done are marked complete.'],
                            ['q' => 'How do I add tasks to a sprint?', 'a' => 'Open a sprint → scroll to the Backlog section → check tasks and click "Add Selected". Tasks must be in the project\'s backlog (not already in another active sprint).'],
                            ['q' => 'What happens to unfinished tasks when I complete a sprint?', 'a' => 'Unfinished tasks automatically return to the project backlog so they can be pulled into the next sprint. Completed tasks stay marked as done.'],
                            ['q' => 'Can I have multiple active sprints?', 'a' => 'No — only one sprint per project can be active at a time. Complete or cancel the current sprint before starting a new one.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── BUGS ── --}}
            <div x-show="activeSection === 'bugs'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-bug-fill text-rose-500"></i> Bug Tracker
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        The Bug Tracker is a dedicated space for logging, triaging, and resolving bugs across all projects.
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How is severity different from priority?', 'a' => 'Severity describes how bad the impact is (Critical breaks the app; Minor is a cosmetic issue). Priority describes when to fix it. A cosmetic bug in the login screen might be minor severity but high priority.'],
                            ['q' => 'What are the bug statuses?', 'a' => 'Open → just reported. In Progress → being fixed. In Review → fix is in review. Resolved → fix is deployed. Closed → confirmed resolved or won\'t fix.'],
                            ['q' => 'Can I link a bug to a task?', 'a' => 'Yes — when creating a bug you can link it to a specific project and assign it to a team member. It also appears in that member\'s task list.'],
                            ['q' => 'What are the severity levels?', 'a' => 'Critical (🔴) → app-breaking. High (🟠) → major feature broken. Medium (🟡) → partial feature broken. Low (🔵) → minor issue. Trivial (⚪) → cosmetic only.'],
                            ['q' => 'How do I filter bugs?', 'a' => 'Use the filter bar above the bug list to filter by severity, status, or assignee. The colored stat pills at the top are also clickable — click one to show only bugs with that status.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── DAILY REPORTS ── --}}
            <div x-show="activeSection === 'daily-reports'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-journal-text text-sky-500"></i> Daily Reports (Standups)
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Daily reports are async standups submitted once per day. They replace the need for synchronous daily meetings and give managers full visibility into the team's progress.
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'What should I put in the summary?', 'a' => 'Write what you actually accomplished today — features completed, reviews done, meetings attended, research performed. Be specific. A manager reading this should know exactly what state the work is in.'],
                            ['q' => 'How do I log tasks in my report?', 'a' => 'Use the Task Breakdown section. For each row, pick one of your open tasks from the dropdown (or leave it unlinked and just describe the work), enter the hours spent, and optionally update the task status. The status change syncs back to the actual task automatically.'],
                            ['q' => 'What if the task I worked on does not exist yet?', 'a' => 'Choose "+ Create a new task…" in the task dropdown. Enter a title, pick the project, type and priority. When you submit the report, the task is created in that project, assigned to you, and linked to the report row. Its status is set from "Status After Today" (In Progress if you leave it unchanged).'],
                            ['q' => 'What does "Status After Today" mean?', 'a' => 'This is the task\'s new status as of end-of-day today. Choosing "Done" will mark the linked task as Done in the project board. Choosing "Blocked" is informational only — it flags the task without changing its status.'],
                            ['q' => 'What is the Blocked checkbox for?', 'a' => 'Check it if you are completely unable to progress on the task due to an external dependency. A red "Blocked" badge shows on the task in Team Standups so managers can follow up. Describe the blocker in the notes or the Blockers box.'],
                            ['q' => 'Can I edit a report after submitting?', 'a' => 'Yes — your reports are editable anytime. Open the report and click Edit. If you re-submit a report for the same day, the existing one is updated rather than duplicated.'],
                            ['q' => 'Where do managers see all reports?', 'a' => 'Managers and admins can access Daily Reports → Team Standups to see all reports for a given date. Reports with blockers are highlighted with an amber border.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── TIME TRACKING ── --}}
            <div x-show="activeSection === 'time-tracking'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-clock-history text-teal-500"></i> Time Tracking
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Time entries let you log hours against specific tasks. This data feeds into project burn rate calculations and manager reports.
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I start a timer?', 'a' => 'Open any task page and click the ⏱ Start Timer button, or click the stopwatch icon on a task card in the Kanban board. There is no separate "Log Time" page — timers are always started from a task. A new timer appears in the ⏱ button in the top navigation bar.'],
                            ['q' => 'How do I pause, resume or stop a timer?', 'a' => 'Click the ⏱ icon in the top navigation bar to open the active timers dropdown. Each timer shows a Pause ⏸ button (while running) or a Resume ▶ button (while paused), and a Stop ⏹ button. Paused timers freeze at the current elapsed time and do not count further seconds until resumed.'],
                            ['q' => 'Where do I see all my time entries?', 'a' => 'Go to Time Tracking in the sidebar. Entries are grouped by date and show a live ticking counter for any still-running (green) or paused (amber) entry. Stopped entries (violet) show the final recorded duration.'],
                            ['q' => 'Can I run more than one timer at once?', 'a' => 'Yes. Each task can have its own timer. All active timers appear in the nav-bar dropdown. Starting a second timer does not stop the first.'],
                            ['q' => 'What is a burn rate?', 'a' => 'Burn rate is the ratio of hours logged to hours estimated for a project. A rate above 100% means the project is consuming more time than planned. Admins see this in the Admin Panel.'],
                            ['q' => 'Can I edit or delete a time entry?', 'a' => 'On the Time Tracking page, click the trash icon next to any entry you own to delete it. Admins can delete any entry.'],
                            ['q' => 'Does the daily report auto-log time?', 'a' => 'No — the hours you enter in the daily report Task Breakdown are for record-keeping in the report. To log tracked time, use the timer on a task directly.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── TEAMS ── --}}
            <div x-show="activeSection === 'teams'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-people-fill text-indigo-500"></i> Teams
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Teams are groups of members. You can assign a team to a project rather than adding individual users, making it easy to manage large groups.
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I create a team?', 'a' => 'Go to Teams in the sidebar → New Team. Give it a name and description, then add members. Teams can be reused across multiple projects.'],
                            ['q' => 'How do I add a member to a team?', 'a' => 'Open the team → click the "Add" button in the Members card (top right of the member list). Select the user from the dropdown and choose their team role (Member, Admin, or Viewer). Only the team owner and members with the Admin role within that team can add or remove members.'],
                            ['q' => 'What are team roles?', 'a' => 'Member — can see the team and its projects. Admin — can add/remove members and manage the team. Viewer — read-only access. These are separate from the global Workzen roles (admin, manager, member).'],
                            ['q' => 'How do I remove a member from a team?', 'a' => 'Open the team → find the member in the list → click the × next to their name. The team owner cannot be removed this way; transfer ownership first if needed.'],
                            ['q' => 'What is the difference between a team member and a project member?', 'a' => 'Team members belong to a named group (e.g., "Frontend Team"). Project members are users directly associated with a specific project. A team can be assigned to a project, adding all its members at once.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── MY TASKS & SEARCH ── --}}
            <div x-show="activeSection === 'my-tasks'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-check2-square text-violet-500"></i> My Tasks & Search
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">My Tasks lists everything assigned to you across all projects, grouped by when it is due. The search box at the top of every page finds projects, tasks and people.</p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'Where do I see all my tasks?', 'a' => 'Open My Tasks in the sidebar. Tasks are grouped into Overdue, Today, This week, Later and No due date. Tick the circle to mark a task done, and filter by project or priority.'],
                            ['q' => 'How do I search?', 'a' => 'Click the search box in the header (or press /) and type at least 2 characters. Use the arrow keys and Enter to jump to a result, or press Enter to see all results.'],
                            ['q' => 'Can I search by ticket number?', 'a' => 'Yes. Type the ticket key, for example MOBILE-12, to jump straight to that task.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── PEOPLE & ORG CHART ── --}}
            <div x-show="activeSection === 'people'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-person-lines-fill text-violet-500"></i> People & Org Chart
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">The People directory lists every active colleague with their title, department, contact details and status. The org chart shows reporting lines.</p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How is the org chart built?', 'a' => 'From each member\'s "Reports to" setting. Admins set it under Members → Edit → Employment.'],
                            ['q' => 'Who can see birthdays and emergency contacts?', 'a' => 'Everyone sees the birthday day and month. The full date of birth and emergency contact are visible only to the person, their manager, admins, and roles with the employees.view-private permission.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── LEAVE & HOLIDAYS ── --}}
            <div x-show="activeSection === 'leave'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-airplane text-violet-500"></i> Leave & Holidays
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">Request time off, track your balances and see who is away. Weekends and company holidays are never counted as leave days.</p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I request leave?', 'a' => 'Go to Leave → Request leave. Pick the type and dates (tick Half day for a single half day) and submit. Your manager is notified; if you have no manager, the leave approvers are.'],
                            ['q' => 'Who approves leave?', 'a' => 'Your manager (the person you report to), admins, and anyone whose role has the leave.approve permission. Approvers see a badge on Leave in the sidebar and use Leave → Approvals. Declining requires a reason.'],
                            ['q' => 'How are balances calculated?', 'a' => 'Each leave type has a yearly allowance. Approved and pending days in the calendar year are deducted. Types with an allowance of 0 (such as Unpaid Leave) are unlimited.'],
                            ['q' => 'Can I cancel a request?', 'a' => 'Yes, while it is pending, or after approval as long as the leave has not started.'],
                            ['q' => 'How are leave types and holidays set up?', 'a' => 'Admins (or the leave.manage permission) use Admin → Leave & Holidays to add leave types, change allowances and add company holidays.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── ATTENDANCE & STATUS ── --}}
            <div x-show="activeSection === 'attendance'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-calendar-check text-violet-500"></i> Attendance & Status
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">Signing in records your start time and signing out records your end time. You can also clock in and out manually and set your availability.</p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I set my status?', 'a' => 'Click the status pill in the top bar and choose Available, Busy, In a meeting, Away or Offline. You show as offline after 5 minutes without activity.'],
                            ['q' => 'Where do I see my hours?', 'a' => "My Attendance shows each day's sign-in and sign-out times and your monthly totals."],
                            ['q' => 'What if I forgot to sign in?', 'a' => 'Ask an admin to add or correct the entry from Admin → Team Attendance. Edited entries show who changed them.'],
                            ['q' => 'Can an admin clock out everyone at once?', 'a' => 'Yes. On the Admin → Team Attendance page, the "Today" section shows a "Clock out all" button when one or more members are still clocked in. Clicking it clocks out every currently clocked-in member in one action. Only admins and users with the attendance.manage permission can see this button.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── ANNOUNCEMENTS ── --}}
            <div x-show="activeSection === 'announcements'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-megaphone text-violet-500"></i> Announcements
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">Company news, policies and events. The latest announcements also appear on the dashboard.</p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'Who can post announcements?', 'a' => 'Admins and roles with the announcements.manage permission.'],
                            ['q' => 'Can an announcement expire?', 'a' => 'Yes. Set "Show until" and it disappears from everyone\'s view after that date. Pinned announcements stay at the top.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── ADMIN ── --}}
            <div x-show="activeSection === 'admin'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-shield-fill-check text-rose-500"></i> Admin Panel
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        The ADMIN section of the sidebar holds: Admin Overview, Members, Roles & Permissions, Team Attendance, Leave & Holidays and Activity Log.
                        Admins see all of them; other roles see only the pages their permissions allow (see Roles & Permissions).
                    </p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'What is shown on the Admin Overview?', 'a' => 'KPI cards (users, projects, tasks, hours logged), a 14-day daily hours chart, task type breakdown, an employee performance table, project burn rates and recent standups. Use the period selector (7 / 14 / 30 / 90 days) at the top to change the window.'],
                            ['q' => 'How is the performance percentage calculated?', 'a' => 'It is the share of a person\'s assigned tasks that are done: tasks completed in the selected period ÷ (open tasks + tasks completed in the period). 70% or more is green, 40–69% amber, below 40% red. Use it as a conversation starter, not a verdict.'],
                            ['q' => 'How do I see one employee in detail?', 'a' => 'Click the view button at the end of their row in the Employee Performance table (or the graph icon on the Members page). You get hours by project, a daily breakdown, open and completed tasks, and their recent standups.'],
                            ['q' => 'What does burn rate tell me?', 'a' => 'Logged hours ÷ estimated hours × 100%. Up to 80% is green, 80–100% amber, and above 100% red (more time spent than estimated). It only appears when the project\'s tasks have estimates.'],
                            ['q' => 'How do I add, edit or remove members?', 'a' => 'Members → Add member. Leave the password empty to generate a temporary one; it is shown once, so copy it before closing the banner. Edit a member to change details, employment info, "Reports to" and roles. Edit also has Reset password and Remove member. The pause button on the list deactivates an account: the person is signed out and cannot sign in until reactivated.'],
                            ['q' => 'What does the Activity Log record?', 'a' => 'Every sign-in, sign-out and failed sign-in, and every create, update and delete anyone makes, with the time, IP address and browser. Expand an entry to see what was submitted (passwords are never recorded). Filter by person, type, keyword or dates, and use Export CSV for a spreadsheet. Times are shown in your own timezone.'],
                            ['q' => 'How do I correct someone\'s attendance?', 'a' => 'Team Attendance → Add entry, or the pencil icon on an existing row. Times are entered in your timezone. Corrected entries are labelled "Admin entry" and show who edited them. The top of the page shows who is online, present, on leave or absent today.'],
                            ['q' => 'Where do I set up leave types and holidays?', 'a' => 'Leave & Holidays. Change each type\'s yearly allowance (0 = unlimited), colour and paid/unpaid status, and add or remove company holidays. A type that has been used cannot be deleted; it is deactivated instead.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── ROLES ── --}}
            <div x-show="activeSection === 'roles'" x-transition>
                <div class="card p-6 space-y-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-person-badge-fill text-violet-500"></i> Roles & Permissions
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        A role is a named bundle of permissions. The three built-in roles are summarised below; admins can change what each role allows and create new roles (for example Team Lead) under Admin → Roles & Permissions. A member can have more than one role and gets every permission from all of them.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800">
                                    <th class="text-left py-2 pr-4 text-slate-500 font-semibold text-xs uppercase tracking-wide">Feature (default setup)</th>
                                    <th class="text-center py-2 px-3 text-slate-500 font-semibold text-xs uppercase tracking-wide">Admin</th>
                                    <th class="text-center py-2 px-3 text-slate-500 font-semibold text-xs uppercase tracking-wide">Manager</th>
                                    <th class="text-center py-2 px-3 text-slate-500 font-semibold text-xs uppercase tracking-wide">Member</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach([
                                    ['Create tasks & bugs, log time, submit daily reports', true, true, true],
                                    ['Request leave, see the team leave calendar', true, true, true],
                                    ['People directory, org chart, announcements (read)', true, true, true],
                                    ['Create & update projects, sprints and teams', true, true, false],
                                    ['View team standups', true, true, false],
                                    ['Approve leave for anyone *', true, true, false],
                                    ['View team attendance', true, true, false],
                                    ['Manage members, roles and permissions', true, false, false],
                                    ['Edit attendance, view the activity log', true, false, false],
                                    ['Leave types & holidays, post announcements', true, false, false],
                                    ['Admin Overview & employee performance', true, false, false],
                                ] as [$action, $admin, $manager, $member])
                                <tr>
                                    <td class="py-2.5 pr-4 text-slate-700 dark:text-slate-300">{{ $action }}</td>
                                    <td class="text-center py-2.5 px-3"><i class="bi {{ $admin ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle text-slate-300 dark:text-slate-700' }}"></i></td>
                                    <td class="text-center py-2.5 px-3"><i class="bi {{ $manager ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle text-slate-300 dark:text-slate-700' }}"></i></td>
                                    <td class="text-center py-2.5 px-3"><i class="bi {{ $member ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle text-slate-300 dark:text-slate-700' }}"></i></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-500">* Anyone can approve leave for the people who report to them, whatever their role.</p>
                    @include('help._section', [
                        'items' => [
                            ['q' => 'How do I change someone\'s roles?', 'a' => 'Admin → Members → pencil icon → tick the roles under "Roles". You cannot remove the admin role from your own account, so there is always at least one admin.'],
                            ['q' => 'How do I create a custom role?', 'a' => 'Admin → Roles & Permissions → New role. Give it a lowercase name (e.g. team_lead) and tick its permissions, or use "Toggle group" to select a whole area. The admin role always keeps every permission and cannot be renamed or deleted. A role still assigned to members cannot be deleted.'],
                            ['q' => 'Which permissions unlock admin pages?', 'a' => 'users.manage (Members), roles.manage (Roles & Permissions), attendance.view / attendance.manage (Team Attendance, read / edit), activity.view (Activity Log), leave.approve (approve anyone\'s leave), leave.manage (Leave & Holidays), announcements.manage (post announcements), employees.view-private (see full birthdays and emergency contacts). Admin Overview itself is admin-only.'],
                            ['q' => 'Can a member see other people\'s daily reports?', 'a' => 'No. Members see only their own reports. Managers and admins can open Daily Reports → Team Standups.'],
                        ]
                    ])
                </div>
            </div>

            {{-- ── TIPS ── --}}
            <div x-show="activeSection === 'tips'" x-transition>
                <div class="space-y-4">

                    {{-- Keyboard shortcuts --}}
                    <div class="card p-6">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                            <i class="bi bi-keyboard text-violet-500"></i> Keyboard Shortcuts
                        </h2>
                        <div class="space-y-1">
                            @php
                            $shortcuts = [
                                ['keys'=>['⌘','K'],'desc'=>'Open Quick Add Task — create a task from anywhere without leaving the page','category'=>'Global'],
                                ['keys'=>['/'],'desc'=>'Jump to the search bar — search tasks, projects, and people by name or ticket key','category'=>'Global'],
                                ['keys'=>['Esc'],'desc'=>'Close any open modal, dropdown, or quick-add panel','category'=>'Global'],
                                ['keys'=>['⌘','Enter'],'desc'=>'Submit a comment on a task detail page','category'=>'Task Detail'],
                                ['keys'=>['Enter'],'desc'=>'Add a task instantly while in the kanban column quick-add input','category'=>'Kanban Board'],
                                ['keys'=>['Esc'],'desc'=>'Cancel the kanban column quick-add without creating a task','category'=>'Kanban Board'],
                            ];
                            $currentCat = null;
                            @endphp
                            @foreach($shortcuts as $sc)
                            @if($sc['category'] !== $currentCat)
                            @php $currentCat = $sc['category']; @endphp
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-4 mb-1 first:mt-0">{{ $currentCat }}</p>
                            @endif
                            <div class="flex items-center gap-4 py-2.5 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <div class="flex items-center gap-1 flex-shrink-0 min-w-[80px]">
                                    @foreach($sc['keys'] as $k)
                                    <kbd class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-md text-xs font-mono font-semibold text-slate-700 dark:text-slate-300 shadow-sm">{{ $k }}</kbd>
                                    @endforeach
                                </div>
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $sc['desc'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tips --}}
                    <div class="card p-6">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                            <i class="bi bi-lightbulb-fill text-amber-500"></i> Power User Tips
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach([
                                ['icon'=>'bi-plus-lg','color'=>'violet','title'=>'Create task from anywhere','desc'=>'The "+ New Task" button in the sidebar takes you to a full creation form where you can pick the project, type, assignees, and labels in one place.'],
                                ['icon'=>'bi-lightning-fill','color'=>'amber','title'=>'Quick-add for speed','desc'=>'Use ⌘K to open the Quick Add modal — just a title, type, project, and priority. Perfect for logging tasks fast while staying in flow.'],
                                ['icon'=>'bi-sidebar-collapse','color'=>'slate','title'=>'Collapse sidebar','desc'=>'Click the arrow in the top workspace header to collapse the sidebar. Icons stay visible for one-click navigation.'],
                                ['icon'=>'bi-moon-stars-fill','color'=>'sky','title'=>'Dark mode','desc'=>'Toggle dark mode with the moon/sun icon in the header. Your preference is saved and applied next visit.'],
                                ['icon'=>'bi-kanban','color'=>'blue','title'=>'Quick add from board','desc'=>'On the kanban board, click "+ Add task" at the bottom of any column and press Enter to create a task in that column instantly.'],
                                ['icon'=>'bi-check2-square','color'=>'emerald','title'=>'Bulk status change','desc'=>'On the task list, check multiple tasks — a floating action bar appears at the bottom — change status or delete them all at once.'],
                                ['icon'=>'bi-journal-plus','color'=>'violet','title'=>'Daily report reminder','desc'=>'A reminder banner shows on the dashboard if you haven\'t submitted today\'s standup. "Submit Daily Report" in the sidebar also takes you straight there.'],
                                ['icon'=>'bi-bug-fill','color'=>'rose','title'=>'Report bugs fast','desc'=>'The "Report Bug" button in the Bug Tracker opens the full Create Task form with Bug pre-selected. Fill in severity, environment, and steps to reproduce.'],
                                ['icon'=>'bi-circle-fill','color'=>'emerald','title'=>'Set your availability','desc'=>'Click the status pill (Available/Busy) in the header to update your status. Teammates see it in the People directory and Directory page.'],
                                ['icon'=>'bi-airplane','color'=>'sky','title'=>'Plan time off early','desc'=>'Check the team leave calendar before requesting leave so you can see who else is off on the same days.'],
                            ] as $tip)
                            <div class="flex gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                <div class="w-8 h-8 rounded-lg bg-{{ $tip['color'] }}-100 dark:bg-{{ $tip['color'] }}-900/20 flex items-center justify-center flex-shrink-0">
                                    <i class="bi {{ $tip['icon'] }} text-{{ $tip['color'] }}-600 dark:text-{{ $tip['color'] }}-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm text-slate-900 dark:text-white">{{ $tip['title'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $tip['desc'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
