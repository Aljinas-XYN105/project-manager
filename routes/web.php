<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ProjectNoteController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\LeaveSettingsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public landing — redirect to dashboard if authed
Route::get('/', fn() => redirect()->route('dashboard'));

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::resource('projects', ProjectController::class);
    Route::patch('projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::patch('projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::post('projects/{project}/members', [ProjectController::class, 'addMember'])->name('projects.members.add');
    Route::delete('projects/{project}/members/{user}', [ProjectController::class, 'removeMember'])->name('projects.members.remove');

    // Project Quick Notes
    Route::get('projects/{project}/notes', [ProjectNoteController::class, 'index'])->name('projects.notes');
    Route::post('projects/{project}/notes', [ProjectNoteController::class, 'store'])->name('projects.notes.store');
    Route::patch('project-notes/{note}', [ProjectNoteController::class, 'update'])->name('project-notes.update');
    Route::delete('project-notes/{note}', [ProjectNoteController::class, 'destroy'])->name('project-notes.destroy');
    Route::post('project-notes/{note}/convert', [ProjectNoteController::class, 'convert'])->name('project-notes.convert');

    // Tasks — nested under project (for create/index) + shallow (for show/edit/update/destroy)
    Route::get('projects/{project}/tasks', [TaskController::class, 'index'])->name('projects.tasks.index');
    Route::get('projects/{project}/tasks/kanban', [TaskController::class, 'kanban'])->name('projects.tasks.kanban');
    Route::get('projects/{project}/tasks/calendar', [TaskController::class, 'calendar'])->name('projects.tasks.calendar');
    Route::get('projects/{project}/tasks/backlog', [TaskController::class, 'backlog'])->name('projects.tasks.backlog');
    Route::get('projects/{project}/tasks/create', [TaskController::class, 'create'])->name('projects.tasks.create');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
    Route::get('tasks/create', [TaskController::class, 'createGlobal'])->name('tasks.create');
    Route::post('tasks/store', [TaskController::class, 'storeGlobal'])->name('tasks.store');
    Route::get('tasks/project-data/{project}', [TaskController::class, 'projectData'])->name('tasks.projectData');
    Route::post('tasks/bulk-update', [TaskController::class, 'bulkUpdate'])->name('tasks.bulk-update');
    Route::post('tasks/quick-store', [TaskController::class, 'quickStore'])->name('tasks.quickStore');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('projects.tasks.show');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');

    // Attachments
    Route::post('tasks/{task}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Columns
    Route::post('projects/{project}/columns', [ColumnController::class, 'store'])->name('columns.store');
    Route::patch('columns/{column}', [ColumnController::class, 'update'])->name('columns.update');
    Route::delete('columns/{column}', [ColumnController::class, 'destroy'])->name('columns.destroy');
    Route::post('projects/{project}/columns/reorder', [ColumnController::class, 'reorder'])->name('columns.reorder');

    // Teams
    Route::resource('teams', TeamController::class);
    Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.remove');

    // Time Entries
    Route::get('time-entries', [TimeEntryController::class, 'index'])->name('time-entries.index');
    Route::post('time-entries', [TimeEntryController::class, 'store'])->name('time-entries.store');
    Route::patch('time-entries/{time_entry}', [TimeEntryController::class, 'update'])->name('time-entries.update');
    Route::delete('time-entries/{time_entry}', [TimeEntryController::class, 'destroy'])->name('time-entries.destroy');
    Route::post('time-entries/start', [TimeEntryController::class, 'start'])->name('time-entries.start');
    Route::post('time-entries/{time_entry}/stop', [TimeEntryController::class, 'stop'])->name('time-entries.stop');
    Route::get('time-entries/running', [TimeEntryController::class, 'running'])->name('time-entries.running');

    // Comments
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::patch('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::patch('comments/{comment}/pin', [CommentController::class, 'pin'])->name('comments.pin');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('time', [ReportController::class, 'timeReport'])->name('time');
        Route::get('projects', [ReportController::class, 'projectReport'])->name('projects');
        Route::get('teams', [ReportController::class, 'teamReport'])->name('teams');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::patch('{id}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Bug tracker
    Route::get('bugs', [TaskController::class, 'bugs'])->name('bugs.index');

    // Sprints (nested under projects)
    Route::prefix('projects/{project}/sprints')->name('projects.sprints.')->group(function () {
        Route::get('/', [SprintController::class, 'index'])->name('index');
        Route::get('/create', [SprintController::class, 'create'])->name('create');
        Route::post('/', [SprintController::class, 'store'])->name('store');
        Route::get('/{sprint}', [SprintController::class, 'show'])->name('show');
        Route::patch('/{sprint}/start', [SprintController::class, 'start'])->name('start');
        Route::patch('/{sprint}/complete', [SprintController::class, 'complete'])->name('complete');
        Route::post('/{sprint}/add-task', [SprintController::class, 'addTask'])->name('add-task');
        Route::delete('/{sprint}/tasks/{task}', [SprintController::class, 'removeTask'])->name('remove-task');
        Route::delete('/{sprint}', [SprintController::class, 'destroy'])->name('destroy');
    });

    // Daily Reports
    Route::prefix('daily-reports')->name('daily-reports.')->group(function () {
        Route::get('/', [DailyReportController::class, 'index'])->name('index');
        Route::get('/team', [DailyReportController::class, 'team'])->name('team');
        Route::get('/create', [DailyReportController::class, 'create'])->name('create');
        Route::post('/', [DailyReportController::class, 'store'])->name('store');
        Route::get('/{dailyReport}', [DailyReportController::class, 'show'])->name('show');
        Route::get('/{dailyReport}/edit', [DailyReportController::class, 'edit'])->name('edit');
        Route::put('/{dailyReport}', [DailyReportController::class, 'update'])->name('update');
        Route::delete('/{dailyReport}', [DailyReportController::class, 'destroy'])->name('destroy');
    });

    // Search & personal task list
    Route::get('search', SearchController::class)->name('search');
    Route::get('my-tasks', MyTasksController::class)->name('my-tasks');

    // People
    Route::get('people', [PeopleController::class, 'index'])->name('people.index');
    Route::get('people/org-chart', [PeopleController::class, 'orgChart'])->name('people.org-chart');

    // Leave
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('leave/calendar', [LeaveController::class, 'calendar'])->name('leave.calendar');
    Route::get('leave/approvals', [LeaveController::class, 'approvals'])->name('leave.approvals');
    Route::patch('leave/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    Route::patch('leave/{leave}/review', [LeaveController::class, 'review'])->name('leave.review');

    // Announcements
    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Attendance & availability (every user)
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::patch('me/availability', [AttendanceController::class, 'availability'])->name('me.availability');

    // Admin (super admin panel)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('employees/{user}', [AdminController::class, 'employee'])->name('employee');

        Route::middleware('role_or_permission:admin|activity.view')->group(function () {
            Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
            Route::get('activity/export', [ActivityLogController::class, 'export'])->name('activity.export');
        });

        Route::get('attendance', [AdminAttendanceController::class, 'index'])
            ->middleware('role_or_permission:admin|attendance.view|attendance.manage')->name('attendance.index');
        Route::middleware('role_or_permission:admin|attendance.manage')->group(function () {
            Route::post('attendance', [AdminAttendanceController::class, 'store'])->name('attendance.store');
            Route::put('attendance/{attendance}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
            Route::delete('attendance/{attendance}', [AdminAttendanceController::class, 'destroy'])->name('attendance.destroy');
        });

        Route::middleware('role_or_permission:admin|leave.manage')->group(function () {
            Route::get('leave', [LeaveSettingsController::class, 'index'])->name('leave.index');
            Route::post('leave/types', [LeaveSettingsController::class, 'storeType'])->name('leave.types.store');
            Route::put('leave/types/{type}', [LeaveSettingsController::class, 'updateType'])->name('leave.types.update');
            Route::delete('leave/types/{type}', [LeaveSettingsController::class, 'destroyType'])->name('leave.types.destroy');
            Route::post('holidays', [LeaveSettingsController::class, 'storeHoliday'])->name('holidays.store');
            Route::delete('holidays/{holiday}', [LeaveSettingsController::class, 'destroyHoliday'])->name('holidays.destroy');
        });

        Route::middleware('role_or_permission:admin|roles.manage')->group(function () {
            Route::resource('roles', RoleController::class)->except('show');
            Route::post('permissions', [RoleController::class, 'storePermission'])->name('permissions.store');
            Route::delete('permissions/{permission}', [RoleController::class, 'destroyPermission'])->name('permissions.destroy');
        });
    });

    // Users — directory is visible to everyone; management needs admin or users.manage
    Route::middleware('role_or_permission:admin|users.manage')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    // Help / Documentation
    Route::get('help', fn() => view('help.index'))->name('help');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

require __DIR__ . '/auth.php';
