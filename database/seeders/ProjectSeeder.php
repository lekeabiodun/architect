<?php

namespace Database\Seeders;

use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users with different roles
        $projectManager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'John Manager',
                'password' => bcrypt('password'),
                'role' => 'project_manager',
            ]
        );

        $contractor1 = User::firstOrCreate(
            ['email' => 'contractor1@example.com'],
            [
                'name' => 'Mike Contractor',
                'password' => bcrypt('password'),
                'role' => 'contractor',
            ]
        );

        $contractor2 = User::firstOrCreate(
            ['email' => 'contractor2@example.com'],
            [
                'name' => 'Sarah Builder',
                'password' => bcrypt('password'),
                'role' => 'contractor',
            ]
        );

        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Jane Client',
                'password' => bcrypt('password'),
                'role' => 'client',
            ]
        );

        // Create sample project
        $project = Project::create([
            'name' => 'Luxury Villa Construction',
            'description' => 'A 5-bedroom luxury villa with modern amenities',
            'client_name' => 'Jane Client',
            'location' => '123 Main Street, Beverly Hills, CA',
            'status' => 'active',
            'planned_start_date' => now()->subDays(30),
            'planned_end_date' => now()->addMonths(6),
            'estimated_budget' => 1500000,
            'manager_id' => $projectManager->id,
        ]);

        // Attach team members
        $project->users()->attach([
            $contractor1->id => ['role' => 'lead_contractor'],
            $contractor2->id => ['role' => 'contractor'],
            $client->id => ['role' => 'client'],
        ]);

        // Define phases with their tasks
        $phasesData = [
            [
                'name' => 'Foundation',
                'description' => 'Site preparation and foundation work',
                'order' => 1,
                'weight' => 15,
                'tasks' => [
                    ['name' => 'Site Survey', 'weight' => 20, 'status' => 'completed'],
                    ['name' => 'Excavation', 'weight' => 30, 'status' => 'completed'],
                    ['name' => 'Pour Foundation', 'weight' => 50, 'status' => 'in_progress'],
                ],
            ],
            [
                'name' => 'Framing',
                'description' => 'Structural framing and roof structure',
                'order' => 2,
                'weight' => 20,
                'tasks' => [
                    ['name' => 'Floor Framing', 'weight' => 40, 'status' => 'pending'],
                    ['name' => 'Wall Framing', 'weight' => 40, 'status' => 'pending'],
                    ['name' => 'Roof Framing', 'weight' => 20, 'status' => 'pending'],
                ],
            ],
            [
                'name' => 'Exterior',
                'description' => 'Roofing, siding, windows, and doors',
                'order' => 3,
                'weight' => 15,
                'tasks' => [
                    ['name' => 'Install Roofing', 'weight' => 40, 'status' => 'pending'],
                    ['name' => 'Install Siding', 'weight' => 30, 'status' => 'pending'],
                    ['name' => 'Install Windows & Doors', 'weight' => 30, 'status' => 'pending'],
                ],
            ],
            [
                'name' => 'MEP Systems',
                'description' => 'Mechanical, Electrical, and Plumbing',
                'order' => 4,
                'weight' => 20,
                'tasks' => [
                    ['name' => 'Rough Plumbing', 'weight' => 30, 'status' => 'pending'],
                    ['name' => 'Electrical Wiring', 'weight' => 40, 'status' => 'pending'],
                    ['name' => 'HVAC Installation', 'weight' => 30, 'status' => 'pending'],
                ],
            ],
            [
                'name' => 'Interior',
                'description' => 'Drywall, flooring, painting, and fixtures',
                'order' => 5,
                'weight' => 20,
                'tasks' => [
                    ['name' => 'Install Drywall', 'weight' => 25, 'status' => 'pending'],
                    ['name' => 'Flooring', 'weight' => 25, 'status' => 'pending'],
                    ['name' => 'Painting', 'weight' => 20, 'status' => 'pending'],
                    ['name' => 'Install Fixtures', 'weight' => 30, 'status' => 'pending'],
                ],
            ],
            [
                'name' => 'Final',
                'description' => 'Final inspections and cleanup',
                'order' => 6,
                'weight' => 10,
                'tasks' => [
                    ['name' => 'Final Inspection', 'weight' => 40, 'status' => 'pending'],
                    ['name' => 'Cleanup', 'weight' => 30, 'status' => 'pending'],
                    ['name' => 'Walkthrough', 'weight' => 30, 'status' => 'pending'],
                ],
            ],
        ];

        $previousPhaseLastTask = null;

        foreach ($phasesData as $phaseData) {
            $phase = Phase::create([
                'project_id' => $project->id,
                'name' => $phaseData['name'],
                'description' => $phaseData['description'],
                'order' => $phaseData['order'],
                'weight' => $phaseData['weight'],
            ]);

            $firstTaskInPhase = null;
            $previousTask = null;

            foreach ($phaseData['tasks'] as $index => $taskData) {
                $task = Task::create([
                    'phase_id' => $phase->id,
                    'name' => $taskData['name'],
                    'status' => $taskData['status'],
                    'weight' => $taskData['weight'],
                    'order' => $index + 1,
                    'estimated_cost' => rand(10000, 100000),
                    'estimated_hours' => rand(20, 200),
                    'assigned_to' => rand(0, 1) ? $contractor1->id : $contractor2->id,
                    'predecessor_task_id' => $previousTask?->id,
                ]);

                if ($index === 0) {
                    $firstTaskInPhase = $task;
                    // First task of phase depends on last task of previous phase
                    if ($previousPhaseLastTask) {
                        $task->update(['predecessor_task_id' => $previousPhaseLastTask->id]);
                    }
                }

                $previousTask = $task;
            }

            $previousPhaseLastTask = $previousTask;

            // Update phase progress
            $phase->updateProgress();
            $phase->updateStatus();
        }

        // Update project progress
        $project->updateProgress();

        // Create another project
        $project2 = Project::create([
            'name' => 'Office Building Renovation',
            'description' => '3-story office building complete renovation',
            'client_name' => 'ABC Corporation',
            'location' => '456 Business Ave, Downtown',
            'status' => 'active',
            'planned_start_date' => now()->addDays(15),
            'planned_end_date' => now()->addMonths(4),
            'estimated_budget' => 800000,
            'manager_id' => $projectManager->id,
        ]);
    }
}
