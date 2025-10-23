# Project Management System - Features Documentation

## Overview
This document describes the comprehensive features added to the project management system, including roles, permissions, material management, inspector workflows, and budget tracking.

---

## 1. Roles and Permissions

### Available Roles

#### 1.1 Super Admin
- **Full system access**
- Can manage all projects, users, materials, and settings
- Override all permissions

#### 1.2 Project Manager
- Manage assigned projects
- View and edit tasks
- Assign tasks to team members
- Manage materials and inventory
- Approve and disburse material requests
- View and manage project budget
- Upload and manage documents

#### 1.3 Contractor
- Work on assigned projects
- Create and manage tasks
- Request materials
- Manage materials (if permission granted)
- View project budget
- Upload documents and add comments

#### 1.4 Inspector
- **Inspect tasks and approve/fail inspections**
- View assigned projects and tasks
- **Confirm material delivery**
- Upload inspection photos and videos
- Add inspection feedback and comments
- Cannot view budget or project management details

#### 1.5 Engineer
- Work on assigned projects
- View and edit tasks
- Complete tasks
- Request materials
- Manage materials
- View budget
- Upload documents

#### 1.6 Developer
- Similar permissions to Engineer
- Work on assigned projects
- Manage technical tasks
- Request materials

#### 1.7 Director
- High-level oversight
- View and edit all assigned projects
- Approve and disburse material requests
- Manage materials and inventory
- View and manage budgets
- Manage team members

#### 1.8 Manager
- Operational management
- Manage assigned projects
- Create and assign tasks
- Approve and disburse material requests
- Manage materials and inventory
- View budget

#### 1.9 Client
- **View-only access to their projects**
- **Cannot view materials/inventory**
- **Cannot view budget details**
- Can view project progress
- Can view tasks status
- Can add comments
- Can view documents

#### 1.10 Worker
- **Limited to tasks assigned to them only**
- Cannot view full project details
- Can complete assigned tasks
- **Can upload photos/videos to tasks**
- Can add comments to their tasks
- Cannot view materials or budget

---

## 2. Inspector Workflow

### 2.1 Task Inspection Process

1. **Task Completion**: Worker/Engineer completes a task
2. **Inspection Required**: Task status changes to require inspection
3. **Inspector Review**: Inspector assigned to project reviews completed task
4. **Inspection Actions**:
   - **Approve**: Task passes inspection
   - **Fail**: Task fails inspection with feedback
   - **Request Re-inspection**: Mark for re-inspection

### 2.2 Inspector Features

```php
// Approve inspection
$task->approveInspection($inspector, 'Work meets quality standards');

// Fail inspection
$task->failInspection($inspector, 'Electrical wiring not up to code', true);

// Request re-inspection
$task->requestReInspection();
```

### 2.3 Inspection Status Values
- `pending`: Awaiting inspection
- `passed`: Inspection approved
- `failed`: Inspection failed
- `re_inspection`: Requires re-inspection

---

## 3. Material Management System

### 3.1 Materials
Materials are items/resources used in construction projects.

**Fields**:
- Name
- Code/SKU
- Description
- Unit (kg, m3, pieces, liters, etc.)
- Unit Cost
- Category (cement, steel, lumber, electrical, etc.)
- Reorder Level
- Specifications

### 3.2 Inventory
Track material quantities across projects, phases, and tasks.

**Fields**:
- Material
- Project/Phase/Task
- Quantity
- Allocated Quantity
- Used Quantity
- Location
- Status (available, allocated, depleted)

**Methods**:
```php
// Allocate materials
$inventory->allocate(100);

// Use materials
$inventory->use(50);

// Update status automatically
$inventory->updateStatus();
```

### 3.3 Material Request Workflow

#### Step 1: Request Creation
Engineers/Developers submit material requests:
- Material needed
- Quantity required
- Required date
- Purpose and justification
- Linked to project/phase/task

#### Step 2: Approval
Director/Manager reviews and:
- **Approves** with approved quantity
- **Rejects** with reason

#### Step 3: Disbursement
Manager/Director disburses materials:
- Records disbursed quantity
- Adds disbursement notes

#### Step 4: Confirmation (Inspector)
Inspector confirms delivery:
- Verifies materials received
- Uploads supporting photos/videos
- Adds confirmation notes

**Workflow Code**:
```php
// Create request
$request = MaterialRequest::create([
    'material_id' => $material->id,
    'project_id' => $project->id,
    'requested_quantity' => 100,
    'requested_by' => $user->id,
    'purpose' => 'Foundation work',
]);

// Approve
$request->approve($director, 100, 'Approved as requested');

// Disburse
$request->disburse($manager, 100, 'Materials dispatched to site');

// Confirm (Inspector)
$request->confirm($inspector, 'Materials received and verified');
```

### 3.4 Material Request Status
- `pending`: Awaiting approval
- `approved`: Approved by director/manager
- `rejected`: Request denied
- `disbursed`: Materials dispatched
- `confirmed`: Inspector confirmed delivery
- `cancelled`: Request cancelled

---

## 4. Task Media and Comments

### 4.1 Workers and Task Documentation
Workers assigned to tasks can:
- Add comments to describe work progress
- Upload photos showing work completed
- Upload videos documenting the process
- Attach supporting documents

### 4.2 Using Documents Model
```php
// Upload photo to task
$document = Document::create([
    'documentable_type' => Task::class,
    'documentable_id' => $task->id,
    'name' => 'Progress Photo',
    'type' => 'photo',
    'file_path' => $path,
    'uploaded_by' => $user->id,
]);

// Upload video
$document = Document::create([
    'documentable_type' => Task::class,
    'documentable_id' => $task->id,
    'type' => 'video',
    // ... other fields
]);
```

### 4.3 Task Comments
```php
// Add comment to task
$comment = TaskComment::create([
    'task_id' => $task->id,
    'user_id' => $user->id,
    'comment' => 'Completed foundation work. See attached photos.',
]);
```

---

## 5. Budget Tracking and Cost Aggregation

### 5.1 Task-Level Costs
Each task tracks:
- `estimated_cost`: Planned cost
- `actual_cost`: Actual cost incurred

### 5.2 Project Budget Tracking

**Calculate Task Costs**:
```php
$costs = $project->calculateTotalTaskCosts();
// Returns:
// [
//     'estimated' => 50000,
//     'actual' => 45000,
//     'variance' => 5000
// ]
```

**Update Project Actual Cost**:
```php
$project->updateActualCostFromTasks();
```

**Budget vs Actual**:
```php
// Budget variance (positive = under budget)
$variance = $project->budget_variance;

// Budget variance percentage
$variancePercent = $project->budget_variance_percentage;

// Budget utilization percentage
$utilization = $project->budget_utilization;
```

### 5.3 Cost Aggregation Flow
1. Tasks track estimated and actual costs
2. Phase costs aggregate from tasks
3. Project costs aggregate from all phases
4. Compare against `estimated_budget`
5. Generate variance reports

---

## 6. Authorization and Access Control

### 6.1 Project Access

**Super Admin**: All projects
**Client**: Only projects they're assigned to (view-only)
**Worker**: Cannot view full project (only their tasks)
**Others**: Projects they're assigned to

### 6.2 Material Access

**Cannot View Materials**:
- Clients
- Workers

**Can View Materials**:
- Super Admin
- Project Manager
- Contractor
- Inspector
- Engineer
- Developer
- Director
- Manager

**Can Manage Materials**:
- Super Admin
- Project Manager
- Director
- Manager
- Others with explicit permission

### 6.3 Budget Access

**Cannot View Budget**:
- Clients
- Workers

**Can View Budget**:
- All other roles on assigned projects

**Can Manage Budget**:
- Super Admin
- Project Manager
- Director

---

## 7. Policy-Based Authorization

### 7.1 Using Policies in Code

```php
// Check if user can view project
if ($user->can('view', $project)) {
    // Show project details
}

// Check if user can manage materials
if ($user->can('manageMaterials', $project)) {
    // Show material management UI
}

// Check if user can inspect task
if ($user->can('inspect', $task)) {
    // Show inspection form
}

// Check if user can approve material request
if ($user->can('approve', $materialRequest)) {
    // Show approval button
}
```

### 7.2 Available Policy Methods

**ProjectPolicy**:
- `view()`, `update()`, `delete()`
- `viewMaterials()`, `manageMaterials()`
- `viewBudget()`

**TaskPolicy**:
- `view()`, `update()`, `delete()`
- `complete()`, `inspect()`
- `addMedia()`

**MaterialRequestPolicy**:
- `view()`, `create()`, `update()`
- `approve()`, `reject()`
- `disburse()`, `confirm()`

**InventoryPolicy**:
- `view()`, `create()`, `update()`
- `allocate()`

---

## 8. User Helper Methods

### 8.1 Role Checking
```php
$user->isSuperAdmin();
$user->isProjectManager();
$user->isContractor();
$user->isInspector();
$user->isEngineer();
$user->isDeveloper();
$user->isDirector();
$user->isManager();
$user->isClient();
$user->isWorker();
```

### 8.2 Permission Checking
```php
$user->canManageMaterials();
$user->canViewMaterials();
$user->canApproveMaterialRequests();
$user->canInspectTasks();
```

### 8.3 Project Access
```php
// Get projects user can access based on role
$projects = $user->getAccessibleProjects();
```

---

## 9. Database Schema

### New Tables Created:

1. **materials**: Material catalog
2. **inventories**: Material inventory tracking
3. **material_requests**: Material request workflow
4. **roles**: User roles (Spatie)
5. **permissions**: Granular permissions (Spatie)
6. **model_has_roles**: User-role assignments
7. **model_has_permissions**: User-permission assignments
8. **role_has_permissions**: Role-permission assignments

### Updated Tables:

1. **tasks**: Added inspector fields
   - `inspected_by`
   - `inspected_at`
   - `inspector_feedback`
   - `requires_re_inspection`

---

## 10. Implementation Examples

### 10.1 Create Material Request (Engineer)
```php
$request = MaterialRequest::create([
    'material_id' => Material::where('name', 'Portland Cement')->first()->id,
    'project_id' => $project->id,
    'phase_id' => $phase->id,
    'task_id' => $task->id,
    'requested_quantity' => 50,
    'required_date' => now()->addDays(3),
    'purpose' => 'Foundation work',
    'justification' => 'Need cement for main foundation pour',
    'requested_by' => auth()->id(),
]);
```

### 10.2 Approve Request (Director)
```php
if ($user->can('approve', $request)) {
    $request->approve($user, 50, 'Approved as requested');
}
```

### 10.3 Inspect Task (Inspector)
```php
if ($user->can('inspect', $task)) {
    if ($quality_good) {
        $task->approveInspection($user, 'Excellent workmanship');
    } else {
        $task->failInspection($user, 'Concrete not properly cured', true);
    }
}
```

### 10.4 Upload Task Photo (Worker)
```php
if ($user->can('addMedia', $task)) {
    $document = $task->documents()->create([
        'name' => 'Foundation Progress',
        'type' => 'photo',
        'file_path' => Storage::put('task-photos', $request->file('photo')),
        'mime_type' => $request->file('photo')->getMimeType(),
        'uploaded_by' => $user->id,
    ]);
}
```

---

## 11. Next Steps

### To Assign Roles to Users:
```php
use Spatie\Permission\Models\Role;

$user->assignRole('inspector');
// or
$user->syncRoles(['inspector', 'engineer']);
```

### To Give Direct Permissions:
```php
$user->givePermissionTo('manage materials');
```

### To Check Permissions:
```php
if ($user->hasPermissionTo('inspect tasks')) {
    // User can inspect
}

if ($user->hasRole('inspector')) {
    // User is inspector
}
```

---

## 12. Summary

✅ **Roles**: 10 comprehensive roles with specific permissions
✅ **Inspector Workflow**: Complete task inspection and approval system
✅ **Material Management**: Full material catalog, inventory, and request workflow
✅ **Budget Tracking**: Task-level costs aggregated to project level
✅ **Access Control**: Role-based authorization with policies
✅ **Worker Features**: Workers can only see their tasks and upload media
✅ **Client Restrictions**: Clients can only view progress, not materials/budget
✅ **Inspector Features**: Confirm material delivery and approve tasks

All features are backed by comprehensive policies, permissions, and database relationships for a secure, scalable project management system.
