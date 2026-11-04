<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Standup;
use App\Models\StandupItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StandupDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Clear old standup + task data ──────────────────────────────
        StandupItem::query()->delete();
        Standup::query()->delete();
        \DB::table('task_assignees')->delete();
        \DB::table('task_labels')->delete();
        Task::query()->delete();

        $today = Carbon::today()->toDateString();

        // ── Members (non-admin) ────────────────────────────────────────
        $members = User::whereHas('roles', fn ($q) => $q->where('name', 'member'))
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'manager'))
            ->get()
            ->keyBy('name');

        // We'll also accept by email prefix if roles differ
        $byEmail = User::all()->keyBy(fn ($u) => explode('@', $u->email)[0]);

        $aljinas   = $byEmail['aljinas']   ?? null;
        $akshay    = $byEmail['akshay']    ?? null;
        $sunitha   = $byEmail['sunitha']   ?? null;
        $pranathi  = $byEmail['pranathi']  ?? null;
        $sajin     = $byEmail['sajin']     ?? null;
        $lidiya    = $byEmail['lidiya']    ?? null;
        $jinu      = $byEmail['jinu']      ?? null;
        $suhana    = $byEmail['suhana']    ?? null;
        $annmaria  = $byEmail['annmaria']  ?? null;
        $aishwarya = $byEmail['aishwarya'] ?? null;
        $sujith    = $byEmail['sujith']    ?? null;
        $nooh      = $byEmail['nooh']      ?? null;

        // ── Projects ───────────────────────────────────────────────────
        $qrMenu        = Project::where('name', 'QR Menu')->first();
        $raedna        = Project::where('name', 'Raedna')->first();
        $saloonPos     = Project::where('name', 'Saloon POS')->first();
        $laundryPos    = Project::where('name', 'Laundry POS')->first();
        $restaurantPos = Project::where('name', 'Restaurant POS')->first();

        // Admin/manager used as creator for seeded tasks
        $admin = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))->first();

        // Helper to get the first (To Do) column of a project
        $col = fn ($project) => $project?->columns()->orderBy('position')->first();

        // ── Task definitions ───────────────────────────────────────────
        // Format: [title, project, status, assignees[], due_days_offset]
        $taskDefs = [

            // ── QR Menu (Aljinas leads, Sunitha + Akshay contribute) ──
            ['Implement QR code scan flow for table ordering',       $qrMenu, 'in_progress', [$aljinas],           0],
            ['Design menu category listing page',                    $qrMenu, 'in_review',   [$aljinas, $sunitha],  1],
            ['Add cart summary slide-in panel',                      $qrMenu, 'todo',        [$sunitha],            2],
            ['Write API endpoint for order placement',               $qrMenu, 'in_progress', [$akshay],             1],
            ['Integrate payment gateway (Razorpay)',                 $qrMenu, 'todo',        [$akshay, $aljinas],   3],
            ['Fix session timeout on idle menu page',                $qrMenu, 'done',        [$sunitha],           -1],

            // ── Raedna (Pranathi + Sajin) ─────────────────────────────
            ['Set up project scaffolding and CI pipeline',           $raedna, 'done',        [$sajin],             -2],
            ['Build user onboarding flow (registration + KYC)',      $raedna, 'in_progress', [$pranathi],           2],
            ['Design dashboard analytics widgets',                   $raedna, 'in_review',   [$pranathi, $sajin],   1],
            ['Implement role-based access control',                  $raedna, 'todo',        [$sajin],              3],
            ['Write unit tests for auth module',                     $raedna, 'todo',        [$pranathi],           4],

            // ── Saloon POS (Lidiya + Jinu) ────────────────────────────
            ['Build appointment booking calendar',                   $saloonPos, 'in_progress', [$lidiya],          1],
            ['Implement staff schedule management',                  $saloonPos, 'in_review',   [$jinu],            2],
            ['Add customer loyalty points module',                   $saloonPos, 'todo',        [$lidiya, $jinu],   4],
            ['Fix invoice PDF generation layout',                    $saloonPos, 'done',        [$jinu],           -1],
            ['Integrate SMS notification for appointments',          $saloonPos, 'todo',        [$lidiya],          3],

            // ── Laundry POS (Suhana + Annmaria) ──────────────────────
            ['Build order intake form with item weight tracking',    $laundryPos, 'in_progress', [$suhana],         1],
            ['Design pickup & delivery scheduling view',             $laundryPos, 'todo',        [$annmaria],       2],
            ['Implement barcode label printing for garments',        $laundryPos, 'in_review',   [$suhana, $annmaria], 2],
            ['Add customer notification on order ready',             $laundryPos, 'todo',        [$suhana],         3],
            ['Fix payment summary report export',                    $laundryPos, 'done',        [$annmaria],      -1],

            // ── Restaurant POS (Aishwarya + Sujith + Nooh) ───────────
            ['Implement table management and floor plan view',       $restaurantPos, 'in_progress', [$aishwarya],   1],
            ['Build kitchen display system (KDS) screen',           $restaurantPos, 'in_progress', [$sujith],       1],
            ['Add modifier groups for menu items',                   $restaurantPos, 'in_review',   [$nooh],         2],
            ['Implement split billing by seat',                      $restaurantPos, 'todo',        [$aishwarya, $sujith], 3],
            ['Write Zomato/Swiggy integration for online orders',   $restaurantPos, 'todo',        [$nooh],         4],
            ['Fix void/refund flow edge case on cash payments',     $restaurantPos, 'done',        [$sujith],      -1],
        ];

        $taskService  = app(\App\Services\TaskService::class);
        $createdTasks = [];

        foreach ($taskDefs as $def) {
            [$title, $project, $status, $assignees, $dueDays] = $def;

            if (!$project || !$admin) continue;

            $column = $col($project);

            $task = $taskService->createTask([
                'project_id'  => $project->id,
                'column_id'   => $column?->id,
                'title'       => $title,
                'type'        => 'task',
                'status'      => $status,
                'priority'    => collect(['low', 'medium', 'high'])->random(),
                'due_date'    => Carbon::today()->addDays($dueDays),
                'created_by'  => $admin->id,
            ], $admin);

            $assigneeIds = collect(array_filter($assignees))->pluck('id')->toArray();
            if (!empty($assigneeIds)) {
                $taskService->assignUsers($task, $assigneeIds, $admin);
            }

            $createdTasks[] = ['task' => $task, 'assignees' => array_filter($assignees)];
        }

        // ── Seed today's standups from each member's assigned tasks ───
        $standupMembers = array_filter([
            $aljinas, $akshay, $sunitha, $pranathi, $sajin,
            $lidiya, $jinu, $suhana, $annmaria, $aishwarya, $sujith, $nooh,
        ]);

        foreach ($standupMembers as $member) {
            // Get this member's tasks
            $myTasks = Task::whereHas('assignees', fn ($q) => $q->where('users.id', $member->id))->get();

            if ($myTasks->isEmpty()) continue;

            $standup = Standup::create([
                'user_id'      => $member->id,
                'standup_date' => $today,
                'blockers'     => null,
            ]);

            foreach ($myTasks as $i => $task) {
                $standup->items()->create([
                    'task_id'     => $task->id,
                    'description' => "[{$task->ticket_key}] {$task->title}",
                    'status'      => $task->status,
                    'sort_order'  => $i,
                ]);
            }
        }

        $this->command->info('✓ Cleared & re-seeded: ' . count($createdTasks) . ' tasks, ' . Standup::count() . ' standups for ' . $today);
    }
}
