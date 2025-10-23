# Quick Reference Guide

## Common Operations

### Assigning Roles to Users

```php
use Spatie\Permission\Models\Role;
use App\Models\User;

// Create a new inspector
$inspector = User::create([
    'name' => 'John Inspector',
    'email' => 'inspector@example.com',
    'password' => bcrypt('password'),
    'role' => 'inspector',
]);
$inspector->assignRole('inspector');

// Create a new worker
$worker = User::create([
    'name' => 'Mike Worker',
    'email' => 'worker@example.com',
    'password' => bcrypt('password'),
    'role' => 'worker',
]);
$worker->assignRole('worker');

// Assign multiple roles
$user->syncRoles(['engineer', 'inspector']);
```

### Assigning Users to Projects

```php
// Attach user to project with role
$project->users()->attach($inspector->id, ['role' => 'inspector']);
$project->users()->attach($worker->id, ['role' => 'worker']);

// Attach multiple users
$project->users()->attach([
    $inspector->id => ['role' => 'inspector'],
    $engineer->id => ['role' => 'engineer'],
    $contractor->id => ['role' => 'contractor'],
]);
```

### Task Assignment and Inspection

```php
// Assign task to worker
$task->assigned_to = $worker->id;
$task->save();

// Worker completes task
$task->status = 'completed';
$task->actual_end_date = now();
$task->save();

// Inspector approves task
$task->approveInspection($inspector, 'Great work!');

// Inspector fails task
$task->failInspection($inspector, 'Needs improvement', true);
```

### Material Request Workflow

```php
// 1. Engineer creates request
$request = MaterialRequest::create([
    'material_id' => $cement->id,
    'project_id' => $project->id,
    'requested_quantity' => 100,
    'requested_by' => $engineer->id,
    'purpose' => 'Foundation work',
]);

// 2. Director approves
$request->approve($director, 100, 'Approved');

// 3. Manager disburses
$request->disburse($manager, 100, 'Sent to site');

// 4. Inspector confirms
$request->confirm($inspector, 'Received and verified');
```

### Budget Tracking

```php
// Get project costs
$costs = $project->calculateTotalTaskCosts();
echo "Estimated: {$costs['estimated']}";
echo "Actual: {$costs['actual']}";
echo "Variance: {$costs['variance']}";

// Update project actual cost from tasks
$project->updateActualCostFromTasks();

// Check budget status
$variance = $project->budget_variance;
$utilization = $project->budget_utilization;
```

### Adding Comments and Media to Tasks

```php
// Worker adds comment
$comment = TaskComment::create([
    'task_id' => $task->id,
    'user_id' => $worker->id,
    'comment' => 'Work completed as requested',
]);

// Worker uploads photo
$document = Document::create([
    'documentable_type' => Task::class,
    'documentable_id' => $task->id,
    'name' => 'Progress Photo',
    'type' => 'photo',
    'file_path' => $photoPath,
    'uploaded_by' => $worker->id,
]);

// Inspector uploads verification photo
$document = Document::create([
    'documentable_type' => Task::class,
    'documentable_id' => $task->id,
    'name' => 'Inspection Photo',
    'type' => 'photo',
    'file_path' => $photoPath,
    'uploaded_by' => $inspector->id,
]);
```

### Authorization Checks in Controllers

```php
// Check if user can view project
$this->authorize('view', $project);

// Check if user can manage materials
$this->authorize('manageMaterials', $project);

// Check if user can inspect task
$this->authorize('inspect', $task);

// Check if user can approve material request
$this->authorize('approve', $materialRequest);

// Check in Blade
@can('view', $project)
    <!-- Show project details -->
@endcan

@can('inspect', $task)
    <!-- Show inspection form -->
@endcan
```

### Inventory Management

```php
// Create inventory for project
$inventory = Inventory::create([
    'material_id' => $material->id,
    'project_id' => $project->id,
    'quantity' => 1000,
    'location' => 'Site Storage A',
]);

// Allocate materials to task
$inventory->allocate(100);

// Use materials
$inventory->use(50);

// Check remaining
$remaining = $inventory->remaining_quantity;

// Update status automatically
$inventory->updateStatus();
```

### Getting User's Accessible Projects

```php
// Get projects based on user role
$projects = $user->getAccessibleProjects();

// Super admin sees all
// Client sees only their projects
// Others see assigned projects
```

### Permission Checks

```php
// In controller or service
if ($user->canManageMaterials()) {
    // Show material management
}

if ($user->canInspectTasks()) {
    // Show inspection interface
}

if ($user->canApproveMaterialRequests()) {
    // Show approval buttons
}

// Check specific permission
if ($user->hasPermissionTo('view budget')) {
    // Show budget information
}
```

### Filtering Data by Role

```php
// Get tasks for current user based on role
if ($user->isWorker()) {
    // Workers only see their tasks
    $tasks = Task::where('assigned_to', $user->id)->get();
} elseif ($user->isInspector()) {
    // Inspectors see tasks in their projects
    $projectIds = $user->projects()->pluck('id');
    $tasks = Task::whereHas('phase.project', function($q) use ($projectIds) {
        $q->whereIn('id', $projectIds);
    })->where('status', 'completed')->get();
} else {
    // Others see all tasks in their projects
    $projectIds = $user->projects()->pluck('id');
    $tasks = Task::whereHas('phase.project', function($q) use ($projectIds) {
        $q->whereIn('id', $projectIds);
    })->get();
}
```

## Role Capabilities Summary

| Feature | Super Admin | PM | Contractor | Inspector | Engineer | Developer | Director | Manager | Client | Worker |
|---------|-------------|----|-----------|-----------|---------|-----------|---------|------------|--------|--------|
| View All Projects | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Assigned Projects | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manage Projects | ✅ | ✅ | ⚠️ | ❌ | ❌ | ❌ | ✅ | ⚠️ | ❌ | ❌ |
| View All Tasks | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| View Assigned Tasks | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Complete Tasks | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ |
| Inspect Tasks | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Materials | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Materials | ✅ | ✅ | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ✅ | ✅ | ❌ | ❌ |
| Request Materials | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| Approve Requests | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Disburse Materials | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Confirm Delivery | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Budget | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Budget | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Upload Documents | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ |
| Add Comments | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ |

**Legend**:
- ✅ = Yes, full access
- ⚠️ = Yes, but requires explicit permission
- ❌ = No access

## Material Categories

Common material categories you might use:
- `cement` - Cement and concrete
- `steel` - Steel and rebar
- `lumber` - Wood and lumber
- `electrical` - Electrical components
- `plumbing` - Plumbing materials
- `roofing` - Roofing materials
- `finishing` - Finishing materials
- `hardware` - Hardware and tools
- `safety` - Safety equipment

## Material Units

Common units of measurement:
- `kg` - Kilograms
- `m3` - Cubic meters
- `m2` - Square meters
- `m` - Meters
- `pieces` - Individual pieces
- `liters` - Liters
- `bags` - Bags (for cement, etc.)
- `sheets` - Sheets (for plywood, etc.)
- `rolls` - Rolls (for wire, etc.)

## Task Inspection Statuses

- `pending` - Task awaiting inspection
- `passed` - Task passed inspection
- `failed` - Task failed inspection
- `re_inspection` - Task requires re-inspection

## Material Request Statuses

- `pending` - Request awaiting approval
- `approved` - Request approved by director/manager
- `rejected` - Request rejected
- `disbursed` - Materials have been dispatched
- `confirmed` - Inspector confirmed delivery
- `cancelled` - Request cancelled

## Inventory Statuses

- `available` - Materials available for use
- `allocated` - Materials reserved for specific task
- `depleted` - Materials exhausted
