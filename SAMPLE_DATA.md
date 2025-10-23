# Sample Data Examples

## Sample Users with Roles

```php
// Super Admin
$superAdmin = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'role' => 'super_admin',
]);
$superAdmin->assignRole('super_admin');

// Project Manager
$projectManager = User::create([
    'name' => 'John Manager',
    'email' => 'manager@example.com',
    'password' => bcrypt('password'),
    'role' => 'project_manager',
]);
$projectManager->assignRole('project_manager');

// Inspector
$inspector = User::create([
    'name' => 'Jane Inspector',
    'email' => 'inspector@example.com',
    'password' => bcrypt('password'),
    'role' => 'inspector',
]);
$inspector->assignRole('inspector');

// Engineer
$engineer = User::create([
    'name' => 'Bob Engineer',
    'email' => 'engineer@example.com',
    'password' => bcrypt('password'),
    'role' => 'engineer',
]);
$engineer->assignRole('engineer');

// Worker
$worker = User::create([
    'name' => 'Mike Worker',
    'email' => 'worker@example.com',
    'password' => bcrypt('password'),
    'role' => 'worker',
]);
$worker->assignRole('worker');

// Client
$client = User::create([
    'name' => 'Sarah Client',
    'email' => 'client@example.com',
    'password' => bcrypt('password'),
    'role' => 'client',
]);
$client->assignRole('client');
```

## Sample Materials

```php
// Cement
$cement = Material::create([
    'name' => 'Portland Cement Type I',
    'code' => 'CEM-PT-001',
    'description' => 'General purpose Portland cement',
    'unit' => 'bags',
    'unit_cost' => 12.50,
    'category' => 'cement',
    'reorder_level' => 100,
    'specifications' => 'ASTM C150 Type I, 94 lb bags',
]);

// Steel Rebar
$rebar = Material::create([
    'name' => 'Steel Rebar #4',
    'code' => 'STL-RB-004',
    'description' => '#4 Grade 60 Rebar',
    'unit' => 'pieces',
    'unit_cost' => 8.75,
    'category' => 'steel',
    'reorder_level' => 200,
    'specifications' => 'Grade 60, 20ft length',
]);

// Lumber
$lumber = Material::create([
    'name' => '2x4x8 Lumber',
    'code' => 'LUM-2X4-8',
    'description' => 'Dimensional lumber 2"x4"x8\'',
    'unit' => 'pieces',
    'unit_cost' => 6.50,
    'category' => 'lumber',
    'reorder_level' => 500,
    'specifications' => 'Kiln dried, select grade',
]);

// Electrical Wire
$wire = Material::create([
    'name' => '12 AWG Copper Wire',
    'code' => 'ELC-WR-12',
    'description' => '12 gauge copper electrical wire',
    'unit' => 'meters',
    'unit_cost' => 0.85,
    'category' => 'electrical',
    'reorder_level' => 1000,
    'specifications' => 'THHN/THWN, 600V rated',
]);

// Plumbing Pipe
$pipe = Material::create([
    'name' => 'PVC Pipe 4"',
    'code' => 'PLM-PVC-4',
    'description' => '4 inch PVC schedule 40 pipe',
    'unit' => 'meters',
    'unit_cost' => 4.25,
    'category' => 'plumbing',
    'reorder_level' => 100,
    'specifications' => 'Schedule 40, 10ft sections',
]);

// Concrete
$concrete = Material::create([
    'name' => 'Ready Mix Concrete 3000 PSI',
    'code' => 'CNC-RMX-3000',
    'description' => 'Ready-mix concrete 3000 PSI',
    'unit' => 'm3',
    'unit_cost' => 120.00,
    'category' => 'cement',
    'reorder_level' => 10,
    'specifications' => '3000 PSI, 4" slump',
]);

// Drywall
$drywall = Material::create([
    'name' => 'Drywall 4x8 1/2"',
    'code' => 'FIN-DRY-48',
    'description' => 'Gypsum drywall sheets',
    'unit' => 'sheets',
    'unit_cost' => 14.50,
    'category' => 'finishing',
    'reorder_level' => 200,
    'specifications' => '4x8 ft, 1/2 inch thick',
]);
```

## Sample Inventory

```php
// Add cement inventory to project
$inventory = Inventory::create([
    'material_id' => $cement->id,
    'project_id' => $project->id,
    'quantity' => 500,
    'allocated_quantity' => 0,
    'used_quantity' => 0,
    'location' => 'Site Storage Area A',
    'status' => 'available',
    'notes' => 'Delivered on ' . now()->format('Y-m-d'),
]);

// Add rebar inventory to specific phase
$inventory = Inventory::create([
    'material_id' => $rebar->id,
    'project_id' => $project->id,
    'phase_id' => $foundationPhase->id,
    'quantity' => 1000,
    'location' => 'Foundation Site',
    'status' => 'available',
]);
```

## Sample Material Request

```php
// Engineer requests materials
$request = MaterialRequest::create([
    'material_id' => $cement->id,
    'project_id' => $project->id,
    'phase_id' => $foundationPhase->id,
    'task_id' => $foundationTask->id,
    'requested_quantity' => 100,
    'required_date' => now()->addDays(3),
    'purpose' => 'Foundation pour',
    'justification' => 'Need cement for main foundation concrete pour scheduled for next week',
    'requested_by' => $engineer->id,
    'status' => 'pending',
]);

// Director approves
$request->approve($director, 100, 'Approved. Material will be available by requested date.');

// Manager disburses
$request->disburse($manager, 100, 'Materials dispatched to site. ETA: Tomorrow 8 AM');

// Inspector confirms delivery
$request->confirm($inspector, 'Materials received in good condition. Verified quantity and quality.');
```

## Sample Task with Inspection

```php
// Create and assign task
$task = Task::create([
    'phase_id' => $foundationPhase->id,
    'name' => 'Pour Foundation Slab',
    'description' => 'Pour main foundation concrete slab',
    'status' => 'in_progress',
    'assigned_to' => $worker->id,
    'estimated_cost' => 5000,
    'planned_start_date' => now(),
    'planned_end_date' => now()->addDays(2),
]);

// Worker completes task
$task->status = 'completed';
$task->actual_end_date = now();
$task->actual_cost = 4800;
$task->save();

// Worker adds comment
TaskComment::create([
    'task_id' => $task->id,
    'user_id' => $worker->id,
    'comment' => 'Foundation slab poured successfully. Concrete is setting properly.',
]);

// Worker uploads photo
Document::create([
    'documentable_type' => Task::class,
    'documentable_id' => $task->id,
    'name' => 'Foundation Pour Progress',
    'type' => 'photo',
    'file_path' => 'task-photos/foundation-pour-001.jpg',
    'uploaded_by' => $worker->id,
]);

// Inspector inspects and approves
$task->approveInspection($inspector, 'Foundation slab meets specifications. Concrete properly placed and finished.');

// Inspector uploads verification photo
Document::create([
    'documentable_type' => Task::class,
    'documentable_id' => $task->id,
    'name' => 'Inspection Verification',
    'type' => 'photo',
    'file_path' => 'task-photos/foundation-inspection-001.jpg',
    'uploaded_by' => $inspector->id,
]);
```

## Sample Project Setup

```php
// Create project
$project = Project::create([
    'name' => 'Residential Building - 123 Main St',
    'description' => 'New 3-story residential building',
    'client_name' => 'Sarah Client',
    'location' => '123 Main Street, City, State',
    'status' => 'active',
    'planned_start_date' => now(),
    'planned_end_date' => now()->addMonths(12),
    'estimated_budget' => 500000,
    'manager_id' => $projectManager->id,
]);

// Assign team to project
$project->users()->attach([
    $projectManager->id => ['role' => 'project_manager'],
    $inspector->id => ['role' => 'inspector'],
    $engineer->id => ['role' => 'engineer'],
    $worker->id => ['role' => 'worker'],
    $client->id => ['role' => 'client'],
]);

// Create phase
$phase = Phase::create([
    'project_id' => $project->id,
    'name' => 'Foundation',
    'description' => 'Site preparation and foundation work',
    'order' => 1,
    'weight' => 15,
    'status' => 'active',
    'planned_start_date' => now(),
    'planned_end_date' => now()->addWeeks(4),
]);

// Create tasks
$task1 = Task::create([
    'phase_id' => $phase->id,
    'name' => 'Site Excavation',
    'description' => 'Excavate site for foundation',
    'status' => 'completed',
    'order' => 1,
    'weight' => 30,
    'estimated_cost' => 8000,
    'actual_cost' => 7500,
    'assigned_to' => $worker->id,
]);

$task2 = Task::create([
    'phase_id' => $phase->id,
    'name' => 'Install Rebar',
    'description' => 'Install rebar grid for foundation',
    'status' => 'in_progress',
    'order' => 2,
    'weight' => 20,
    'estimated_cost' => 5000,
    'assigned_to' => $worker->id,
    'predecessor_task_id' => $task1->id,
]);
```

## Complete Workflow Example

```php
// 1. Engineer requests materials for upcoming task
$materialRequest = MaterialRequest::create([
    'material_id' => $cement->id,
    'project_id' => $project->id,
    'phase_id' => $phase->id,
    'task_id' => $task2->id,
    'requested_quantity' => 50,
    'required_date' => now()->addDays(2),
    'purpose' => 'Foundation slab pour',
    'justification' => 'Required for scheduled foundation work',
    'requested_by' => $engineer->id,
]);

// 2. Director reviews and approves
if ($director->can('approve', $materialRequest)) {
    $materialRequest->approve($director, 50, 'Approved for scheduled work');
}

// 3. Manager disburses materials
if ($manager->can('disburse', $materialRequest)) {
    $materialRequest->disburse($manager, 50, 'Materials ready for pickup at warehouse');
}

// 4. Inspector confirms delivery
if ($inspector->can('confirm', $materialRequest)) {
    $materialRequest->confirm($inspector, 'Materials received and verified at site');
    
    // Upload delivery photo
    Document::create([
        'documentable_type' => MaterialRequest::class,
        'documentable_id' => $materialRequest->id,
        'name' => 'Material Delivery Confirmation',
        'type' => 'photo',
        'file_path' => 'material-delivery/cement-delivery-001.jpg',
        'uploaded_by' => $inspector->id,
    ]);
}

// 5. Worker starts and completes task
$task2->status = 'in_progress';
$task2->actual_start_date = now();
$task2->save();

// Worker adds progress comment
TaskComment::create([
    'task_id' => $task2->id,
    'user_id' => $worker->id,
    'comment' => 'Started rebar installation. Materials received.',
]);

// Worker completes task
$task2->status = 'completed';
$task2->actual_end_date = now()->addDays(1);
$task2->actual_cost = 4700;
$task2->save();

// Upload completion photo
Document::create([
    'documentable_type' => Task::class,
    'documentable_id' => $task2->id,
    'name' => 'Rebar Installation Complete',
    'type' => 'photo',
    'file_path' => 'task-photos/rebar-complete-001.jpg',
    'uploaded_by' => $worker->id,
]);

// 6. Inspector inspects task
if ($inspector->can('inspect', $task2)) {
    $task2->approveInspection(
        $inspector, 
        'Rebar grid properly installed according to specifications. Spacing and tie-offs correct.'
    );
    
    // Upload inspection photo
    Document::create([
        'documentable_type' => Task::class,
        'documentable_id' => $task2->id,
        'name' => 'Rebar Inspection',
        'type' => 'photo',
        'file_path' => 'task-photos/rebar-inspection-001.jpg',
        'uploaded_by' => $inspector->id,
    ]);
}

// 7. Update phase and project progress
$phase->updateProgress();
$project->updateProgress();

// 8. Update project costs from tasks
$project->updateActualCostFromTasks();

// 9. Check budget status
$costs = $project->calculateTotalTaskCosts();
echo "Budget Status:\n";
echo "Estimated: $" . number_format($costs['estimated'], 2) . "\n";
echo "Actual: $" . number_format($costs['actual'], 2) . "\n";
echo "Variance: $" . number_format($costs['variance'], 2) . "\n";
echo "Utilization: " . number_format($project->budget_utilization, 2) . "%\n";
```

## Testing Access Control

```php
// Test worker access (should only see their tasks)
$workerTasks = Task::where('assigned_to', $worker->id)->get();
// Worker cannot view full project

// Test inspector access (can see assigned projects, inspect tasks)
if ($inspector->can('inspect', $task2)) {
    echo "Inspector can inspect this task\n";
}

// Test client access (can only view progress, no materials/budget)
if ($client->can('view', $project)) {
    echo "Client can view project progress\n";
}
if (!$client->canViewMaterials()) {
    echo "Client cannot view materials\n";
}

// Test material request approval
if ($director->canApproveMaterialRequests()) {
    echo "Director can approve material requests\n";
}
```
