<?php

namespace Database\Seeders;

use App\Models\BillOfQuantity;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\MaterialRequest;
use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Stages demo data for the narrated role-by-role walkthrough videos.
 * Idempotent: re-running resets the demo project and its dependent records,
 * so a recording can always start from the same state.
 */
class WalkthroughSeeder extends Seeder
{
    private const PROJECT_NAME = 'Riverside Office Complex';

    public function run(): void
    {
        $admin = User::where('email', 'admin@projectarchitect.com')->firstOrFail();
        $pm = User::where('email', 'pm@projectarchitect.com')->firstOrFail();
        $inspector = User::where('email', 'inspector@projectarchitect.com')->firstOrFail();
        $engineer = User::where('email', 'engineer@projectarchitect.com')->firstOrFail();
        $worker = User::where('email', 'worker@projectarchitect.com')->firstOrFail();
        $client = User::where('email', 'client@projectarchitect.com')->firstOrFail();
        $users = compact('admin', 'pm', 'inspector', 'engineer', 'worker', 'client');

        $this->reset($users);
        $this->quietCompetingDemoData($users);

        // Users created live during recording (admin's Team demo) are removed on reset
        User::where('email', 'tunde.foreman@projectarchitect.com')->delete();

        $project = Project::create([
            'name' => self::PROJECT_NAME,
            'description' => 'A four-storey riverside office complex with underground parking, landscaped grounds, and a rooftop terrace.',
            'client_id' => $client->id,
            'location' => '14 Riverside Drive, Victoria Island, Lagos',
            'status' => 'active',
            'planned_start_date' => now()->subMonths(2),
            'planned_end_date' => now()->addMonths(10),
            'estimated_budget' => 2400000,
            'currency' => 'USD',
            'manager_id' => $pm->id,
            'inspector_id' => $inspector->id,
        ]);

        $project->users()->attach([
            $pm->id => ['role' => 'project_manager'],
            $engineer->id => ['role' => 'engineer'],
            $worker->id => ['role' => 'worker'],
            $inspector->id => ['role' => 'inspector'],
            $client->id => ['role' => 'client'],
        ]);

        [$sitePrep, $foundation, $finishing] = $this->phases($project);
        $tasks = $this->tasks($sitePrep, $foundation, $finishing, $users);
        $boq = $this->billOfQuantities($project);
        $this->materialRequests($project, $boq, $users);
        $this->timeEntries($project, $tasks, $users);
        $this->leave($users);
    }

    private function reset(array $u): void
    {
        // 'Harbor View Apartments' is created live during the admin recording
        foreach (Project::whereIn('name', [self::PROJECT_NAME, 'Harbor View Apartments'])->get() as $old) {
            MaterialRequest::where('project_id', $old->id)->delete();
            TimeEntry::where('project_id', $old->id)->delete();
            BillOfQuantity::where('project_id', $old->id)->delete();
            foreach ($old->phases as $phase) {
                Task::where('phase_id', $phase->id)->delete();
            }
            Phase::where('project_id', $old->id)->delete();
            $old->users()->detach();
            $old->delete();
        }

        // Leave data is user-scoped, not project-scoped: reset all demo users' records
        LeaveRequest::whereIn('user_id', collect($u)->pluck('id'))->delete();
        LeaveBalance::whereIn('user_id', collect($u)->pluck('id'))->delete();
        // Stray active clock-ins would block the live clock-in demo
        TimeEntry::whereIn('user_id', collect($u)->pluck('id'))->whereNull('clock_out')->delete();
    }

    /**
     * The default ProjectSeeder demo project ("Luxury Villa Construction") assigns the
     * same demo users to its tasks, so it clutters every task list and inspection queue
     * during the walkthrough. Detach the demo users from it and unassign their tasks so
     * the recording shows only the Riverside project. Non-destructive: the Luxury Villa
     * project, phases, and tasks remain; only demo-user membership/assignment is cleared.
     */
    private function quietCompetingDemoData(array $u): void
    {
        $demoIds = collect($u)->pluck('id')->all();

        foreach (Project::where('name', '!=', self::PROJECT_NAME)->get() as $other) {
            $other->users()->detach($demoIds);
            Task::whereHas('phase', fn ($q) => $q->where('project_id', $other->id))
                ->whereIn('assigned_to', $demoIds)
                ->update(['assigned_to' => null, 'inspected_by' => null]);
        }
    }

    private function phases(Project $project): array
    {
        $sitePrep = Phase::create([
            'project_id' => $project->id, 'name' => 'Site Preparation',
            'description' => 'Clearing, grading, and site establishment', 'order' => 1, 'weight' => 20,
        ]);
        $foundation = Phase::create([
            'project_id' => $project->id, 'name' => 'Foundation & Structure',
            'description' => 'Excavation, foundation, and structural frame', 'order' => 2, 'weight' => 45,
        ]);
        $finishing = Phase::create([
            'project_id' => $project->id, 'name' => 'Finishing & Handover',
            'description' => 'Interior finishing, external works, and handover', 'order' => 3, 'weight' => 35,
        ]);

        return [$sitePrep, $foundation, $finishing];
    }

    private function tasks(Phase $sitePrep, Phase $foundation, Phase $finishing, array $u): array
    {
        $t = [];

        // Completed and already passed inspection — history/progress display
        $t['cleared'] = Task::create([
            'phase_id' => $sitePrep->id, 'name' => 'Clear and grade site', 'order' => 1, 'weight' => 30,
            'status' => 'completed', 'assigned_to' => $u['worker']->id,
            'inspection_status' => 'passed', 'inspected_by' => $u['inspector']->id,
            'inspected_at' => now()->subDays(20), 'inspector_feedback' => 'Site cleared to specification. Grading verified with laser level.',
            'planned_start_date' => now()->subMonths(2), 'planned_end_date' => now()->subDays(45),
            'actual_start_date' => now()->subMonths(2), 'actual_end_date' => now()->subDays(46),
            'estimated_cost' => 18000, 'actual_cost' => 17250, 'estimated_hours' => 120,
        ]);

        // Completed, awaiting inspection — inspector PASSES this on camera
        $t['compaction'] = Task::create([
            'phase_id' => $sitePrep->id, 'name' => 'Soil compaction test', 'order' => 2, 'weight' => 30,
            'status' => 'completed', 'assigned_to' => $u['worker']->id, 'inspection_status' => 'pending',
            'planned_start_date' => now()->subDays(44), 'planned_end_date' => now()->subDays(40),
            'actual_start_date' => now()->subDays(44), 'actual_end_date' => now()->subDays(2),
            'estimated_cost' => 6500, 'estimated_hours' => 40,
        ]);

        // Completed, awaiting inspection — inspector FAILS this on camera
        $t['fencing'] = Task::create([
            'phase_id' => $sitePrep->id, 'name' => 'Perimeter fencing and signage', 'order' => 3, 'weight' => 40,
            'status' => 'completed', 'assigned_to' => $u['worker']->id, 'inspection_status' => 'pending',
            'planned_start_date' => now()->subDays(42), 'planned_end_date' => now()->subDays(35),
            'actual_start_date' => now()->subDays(42), 'actual_end_date' => now()->subDays(1),
            'estimated_cost' => 12000, 'estimated_hours' => 80,
        ]);

        // In progress, assigned to worker — worker COMPLETES this on camera
        $t['excavation'] = Task::create([
            'phase_id' => $foundation->id, 'name' => 'Excavate foundation trenches', 'order' => 1, 'weight' => 20,
            'status' => 'in_progress', 'assigned_to' => $u['worker']->id, 'inspection_status' => 'pending',
            'planned_start_date' => now()->subDays(10), 'planned_end_date' => now()->addDays(5),
            'actual_start_date' => now()->subDays(10),
            'estimated_cost' => 32000, 'estimated_hours' => 200,
        ]);

        // Pending, assigned to worker — worker STARTS this on camera
        $t['rebar'] = Task::create([
            'phase_id' => $foundation->id, 'name' => 'Install rebar cages', 'order' => 2, 'weight' => 25,
            'status' => 'pending', 'assigned_to' => $u['worker']->id, 'inspection_status' => 'pending',
            'planned_start_date' => now()->addDays(3), 'planned_end_date' => now()->addDays(15),
            'estimated_cost' => 48000, 'estimated_hours' => 260, 'predecessor_task_id' => $t['excavation']->id,
        ]);

        // In progress, assigned to engineer — engineer posts progress on camera
        $t['concrete'] = Task::create([
            'phase_id' => $foundation->id, 'name' => 'Pour concrete foundation', 'order' => 3, 'weight' => 30,
            'status' => 'in_progress', 'assigned_to' => $u['engineer']->id, 'inspection_status' => 'pending',
            'planned_start_date' => now()->subDays(4), 'planned_end_date' => now()->addDays(20),
            'actual_start_date' => now()->subDays(4),
            'estimated_cost' => 96000, 'estimated_hours' => 320,
        ]);

        $t['steel'] = Task::create([
            'phase_id' => $foundation->id, 'name' => 'Structural steel erection', 'order' => 4, 'weight' => 25,
            'status' => 'pending', 'assigned_to' => $u['engineer']->id, 'inspection_status' => 'pending',
            'planned_start_date' => now()->addDays(25), 'planned_end_date' => now()->addDays(60),
            'estimated_cost' => 185000, 'estimated_hours' => 540, 'predecessor_task_id' => $t['concrete']->id,
        ]);

        // Unassigned — PM assigns it on camera
        $t['drywall'] = Task::create([
            'phase_id' => $finishing->id, 'name' => 'Interior drywall and partitions', 'order' => 1, 'weight' => 40,
            'status' => 'pending', 'inspection_status' => 'pending',
            'planned_start_date' => now()->addDays(90), 'planned_end_date' => now()->addDays(130),
            'estimated_cost' => 74000, 'estimated_hours' => 400,
        ]);

        return $t;
    }

    private function billOfQuantities(Project $project): array
    {
        $items = [
            ['BOQ-001', 'Portland Cement 42.5R', 'bags', 500, 450, 12.50, 'Concrete Works'],
            ['BOQ-002', 'Steel Rebar 16mm', 'tons', 40, 36, 850.00, 'Reinforcement'],
            ['BOQ-003', 'Sharp Sand', 'm3', 120, 110, 45.00, 'Concrete Works'],
            ['BOQ-004', 'Sandcrete Blocks 9-inch', 'pcs', 8000, 7500, 1.20, 'Masonry'],
            ['BOQ-005', 'Emulsion Paint (white)', 'gallons', 200, 180, 28.00, 'Finishing'],
        ];

        $boq = [];
        foreach ($items as $i => [$code, $desc, $unit, $qty, $reqable, $rate, $cat]) {
            $boq[$code] = BillOfQuantity::create([
                'project_id' => $project->id, 'item_code' => $code, 'description' => $desc,
                'unit' => $unit, 'quantity' => $qty, 'requestable_quantity' => $reqable,
                'unit_rate' => $rate, 'total_amount' => $qty * $rate, 'category' => $cat, 'order' => $i + 1,
            ]);
        }

        return $boq;
    }

    private function materialRequests(Project $project, array $boq, array $u): void
    {
        // Pending — admin approves on camera
        MaterialRequest::create([
            'project_id' => $project->id, 'bill_of_quantity_id' => $boq['BOQ-001']->id,
            'requested_quantity' => 150, 'required_date' => now()->addDays(7),
            'purpose' => 'Foundation pour for grid lines A to D',
            'justification' => 'Concrete pour scheduled next week; current stock on site is exhausted.',
            'requested_by' => $u['engineer']->id, 'status' => 'pending', 'created_at' => now()->subDays(2),
        ]);

        // Pending — PM approves on camera
        MaterialRequest::create([
            'project_id' => $project->id, 'bill_of_quantity_id' => $boq['BOQ-002']->id,
            'requested_quantity' => 10, 'required_date' => now()->addDays(10),
            'purpose' => 'Rebar cages for foundation trenches',
            'justification' => 'Cage fabrication starts once excavation passes inspection.',
            'requested_by' => $u['engineer']->id, 'status' => 'pending', 'created_at' => now()->subDay(),
        ]);

        // Approved — PM disburses on camera
        MaterialRequest::create([
            'project_id' => $project->id, 'bill_of_quantity_id' => $boq['BOQ-003']->id,
            'requested_quantity' => 60, 'approved_quantity' => 55, 'required_date' => now()->addDays(3),
            'purpose' => 'Screed bed for ground floor slab',
            'requested_by' => $u['engineer']->id, 'approved_by' => $u['pm']->id,
            'approved_at' => now()->subDay(), 'approval_notes' => 'Approved at 55 cubic metres to match remaining slab area.',
            'status' => 'approved', 'created_at' => now()->subDays(3),
        ]);

        // Disbursed — inspector confirms delivery on camera
        MaterialRequest::create([
            'project_id' => $project->id, 'bill_of_quantity_id' => $boq['BOQ-004']->id,
            'requested_quantity' => 2000, 'approved_quantity' => 2000, 'disbursed_quantity' => 2000,
            'required_date' => now()->addDay(),
            'purpose' => 'Perimeter wall first lift',
            'requested_by' => $u['engineer']->id, 'approved_by' => $u['pm']->id, 'disbursed_by' => $u['pm']->id,
            'approved_at' => now()->subDays(2), 'disbursed_at' => now()->subDay(),
            'disbursement_notes' => 'Released from central store; delivery truck dispatched.',
            'status' => 'disbursed', 'created_at' => now()->subDays(4),
        ]);
    }

    private function timeEntries(Project $project, array $tasks, array $u): void
    {
        // A week of completed shifts so the admin timesheet has data to filter/edit
        foreach ([['worker', 'excavation'], ['engineer', 'concrete'], ['inspector', null], ['pm', null]] as [$who, $taskKey]) {
            foreach (range(1, 5) as $daysAgo) {
                $in = now()->subDays($daysAgo)->setTime(8, [0, 5, 10, 15][$daysAgo % 4], 0);
                TimeEntry::create([
                    'user_id' => $u[$who]->id,
                    'project_id' => $project->id,
                    'task_id' => $taskKey ? $tasks[$taskKey]->id : null,
                    'clock_in' => $in,
                    'clock_out' => $in->copy()->setTime(17, [0, 10, 20, 30][$daysAgo % 4], 0),
                    'break_duration' => 45,
                    'notes' => 'Demo shift — ' . str_replace('_', ' ', $who),
                    'location' => 'Riverside site, Victoria Island',
                ]);
            }
        }
    }

    private function leave(array $u): void
    {
        foreach ($u as $key => $user) {
            if ($key === 'client') {
                continue;
            }
            foreach ([['vacation', 20], ['sick', 10], ['personal', 5]] as [$type, $days]) {
                LeaveBalance::create([
                    'user_id' => $user->id, 'leave_type' => $type,
                    'balance_days' => $days, 'year' => now()->year, 'accrual_rate' => 0,
                ]);
            }
        }

        // Pending — admin approves on camera
        LeaveRequest::create([
            'user_id' => $u['worker']->id, 'leave_type' => 'vacation',
            'start_date' => now()->addDays(21), 'end_date' => now()->addDays(25),
            'reason' => 'Family trip planned before the foundation pour milestone.',
            'status' => 'pending', 'duration_days' => 5, 'created_at' => now()->subDays(2),
        ]);

        // Pending — PM rejects on camera (shows the rejection flow)
        LeaveRequest::create([
            'user_id' => $u['inspector']->id, 'leave_type' => 'personal',
            'start_date' => now()->addDays(5), 'end_date' => now()->addDays(9),
            'reason' => 'Personal engagement out of town during inspection window.',
            'status' => 'pending', 'duration_days' => 5, 'created_at' => now()->subDay(),
        ]);

        // Approved history so lists don't look empty
        LeaveRequest::create([
            'user_id' => $u['engineer']->id, 'leave_type' => 'sick',
            'start_date' => now()->subDays(12), 'end_date' => now()->subDays(11),
            'reason' => 'Recovering from a fever; site duties covered by the duty engineer.',
            'status' => 'approved', 'approved_by' => $u['pm']->id, 'approved_at' => now()->subDays(13),
            'duration_days' => 2, 'created_at' => now()->subDays(14),
        ]);
    }
}
