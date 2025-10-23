# Enhanced Project Management System

## Overview
This document outlines the enhanced project management system with client selection, role-based team assignment, and permission-based access control.

## Key Features Implemented

### 1. Client Management
✅ **Client Model and Database**
- Created `Client` model with fields: name, company, email, phone, address, notes
- Optional link to User account for client portal access
- Clients table with proper relationships

✅ **Client Selection During Project Creation**
- Dropdown to select from existing clients in the system
- Client information automatically populated
- Fallback to manual client_name entry if client not selected

### 2. Role-Based Team Assignment

✅ **Project Manager Assignment**
- Assign a project manager during project creation
- Project manager has full edit permissions
- Can manage team members and project settings

✅ **Inspector Assignment**
- Dedicated inspector field for quality control
- Inspectors can view project and perform inspections
- One inspector per project (can be changed)

✅ **Team Member Management**
- Add multiple team members to a project
- Team members join with their existing system role
- **Role Inheritance**: 
  - If a user is a "Project Manager" in the system → they join as "Project Manager"
  - If a user is an "Inspector" → they join as "Inspector"
  - If a user is a "Worker" → they join as "Worker"
- Roles stored in `project_user` pivot table

### 3. Permission-Based Access Control

#### View Permissions
**Who can see a project:**
- ✅ Super Admins - Can see all projects
- ✅ Project Manager - Can see their assigned projects
- ✅ Inspector - Can see projects they're assigned to
- ✅ Team Members - Can see projects they're added to
- ✅ Clients - Can see projects linked to their client record
- ❌ Workers - Cannot see full project details (task-level only)
- ❌ Non-assigned users - Cannot see the project

#### Edit Permissions
**Who can edit a project:**
- ✅ Super Admins - Can edit all projects
- ✅ Project Manager - Can edit their projects
- ✅ Users with "edit projects" permission AND assigned to the project
- ❌ Team members without explicit permission - Can view only
- ❌ Inspectors - Can view and inspect but not edit project settings
- ❌ Clients - Can view only

#### Team Management Permissions
**Who can add/remove team members:**
- ✅ Super Admins
- ✅ Project Manager of the project
- ✅ Users with "edit projects" permission AND assigned to the project
- ❌ Regular team members - Cannot modify team

## Database Schema

### Clients Table
```sql
- id
- name
- company (nullable)
- email (unique)
- phone (nullable)
- address (nullable)
- notes (nullable)
- user_id (nullable, foreign key to users)
- timestamps
```

### Projects Table (Updated)
```sql
Added fields:
- client_id (nullable, foreign key to clients)
- inspector_id (nullable, foreign key to users)

Existing fields:
- manager_id (foreign key to users)
- client_name (manual entry fallback)
- ... other project fields
```

### Project_User Pivot Table (Existing)
```sql
- id
- project_id (foreign key)
- user_id (foreign key)
- role (stores the user's system role at time of assignment)
- timestamps
- unique constraint on (project_id, user_id)
```

## Model Relationships

### Project Model
```php
// New relationships
public function client(): BelongsTo
public function inspector(): BelongsTo

// Existing relationships
public function manager(): BelongsTo
public function users(): BelongsToMany // team members
```

### Client Model
```php
public function user(): BelongsTo       // linked user account
public function projects(): HasMany     // all projects for this client
```

## Authorization Flow

### Creating a Project
1. User must have `create projects` permission
2. User selects:
   - Client from dropdown (optional, falls back to client_name)
   - Project Manager from dropdown
   - Inspector from dropdown (optional)
   - Team Members (multiple select)
3. System automatically assigns roles to team members based on their system role

### Viewing a Project
1. Check if user is super admin → Allow
2. Check if user is project manager → Allow
3. Check if user is inspector → Allow
4. Check if user is in project team → Allow
5. Check if user is linked client → Allow
6. Otherwise → Deny

### Editing a Project
1. Check if user is super admin → Allow
2. Check if user is project manager → Allow
3. Check if user has "edit projects" permission AND is in team → Allow
4. Otherwise → Deny (including inspectors and clients)

### Managing Team Members
1. Check if user is super admin → Allow
2. Check if user is project manager → Allow
3. Check if user has "edit projects" permission AND is in team → Allow
4. Otherwise → Deny

## User Experience

### Project Creation Flow
1. Navigate to Projects → Click "Create Project"
2. **Basic Information**
   - Project Name (required)
   - Description
   - Location
   - Status (active, on_hold, completed, cancelled)

3. **Client Selection**
   - Select existing client from dropdown OR
   - Enter client name manually
   - If client selected, client_name auto-filled

4. **Team Assignment**
   - Select Project Manager (required)
   - Select Inspector (optional)
   - Select Team Members (optional, multi-select)

5. **Budget & Timeline**
   - Estimated Budget
   - Currency (USD, NGN)
   - Planned Start/End Dates

6. Click "Create Project"
   - Project created
   - Team members attached with their system roles

### Project List View
- Users see only projects they have access to
- Super admins see all projects
- Filters: Status, Search (name, client, location)
- Shows: Client, Manager, Progress, Budget

### Project Edit Flow
- Only accessible if user has edit permissions
- Can modify all project settings
- Can add/remove team members (if has permission)
- Team members sync with their current system roles

## Technical Implementation

### Files Created
1. **`app/Models/Client.php`** - Client model
2. **`database/migrations/2025_10_23_180558_create_clients_table.php`** - Clients table
3. **`database/migrations/2025_10_23_180633_add_client_and_inspector_to_projects_table.php`** - Add fields to projects

### Files Modified
1. **`app/Models/Project.php`**
   - Added `client_id`, `inspector_id` to fillable
   - Added `client()`, `inspector()` relationships

2. **`app/Livewire/Project/Index.php`**
   - Added client, inspector, team member fields
   - Updated validation rules
   - Added team member synchronization logic
   - Added authorization checks
   - Filter projects based on user access

3. **`app/Policies/ProjectPolicy.php`**
   - Updated `view()` to include inspector and client checks
   - Added `manageTeam()` policy method

## Permission Matrix

| Action | Super Admin | Project Manager | Inspector | Team Member (with permission) | Team Member (no permission) | Client | Worker |
|--------|------------|-----------------|-----------|------------------------------|----------------------------|--------|---------|
| Create Project | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| View Project | ✅ | ✅ (own) | ✅ (assigned) | ✅ (assigned) | ✅ (assigned) | ✅ (linked) | ❌ |
| Edit Project | ✅ | ✅ (own) | ❌ | ✅ (if has permission) | ❌ | ❌ | ❌ |
| Delete Project | ✅ | ✅ (own) | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage Team | ✅ | ✅ (own) | ❌ | ✅ (if has permission) | ❌ | ❌ | ❌ |
| View Budget | ✅ | ✅ (assigned) | ✅ (if has permission) | ✅ (if has permission) | ❌ | ❌ | ❌ |

## Security Considerations

1. **Authorization Enforcement**
   - All Livewire actions check authorization before execution
   - Policies enforce permissions at model level
   - Database queries filtered by user access

2. **Role Integrity**
   - User roles stored in pivot table
   - Cannot be modified directly
   - Synced from user's system role

3. **Client Privacy**
   - Clients see only their own projects
   - No access to other client data
   - Limited to project viewing only

4. **Team Visibility**
   - Team members see only assigned projects
   - Cannot discover unassigned projects
   - Filtered at query level

## Next Steps & Future Enhancements

### Recommended Additions
- [ ] Client management UI (CRUD operations)
- [ ] Bulk team member actions
- [ ] Project transfer between managers
- [ ] Team member invitation system
- [ ] Activity log for project changes
- [ ] Email notifications for team assignments
- [ ] Client portal dashboard
- [ ] Role-specific project views
- [ ] Advanced permission granularity

### Testing Checklist
- [ ] Create project with client selection
- [ ] Create project with team members
- [ ] Verify role inheritance on team assignment
- [ ] Test edit permissions for each user type
- [ ] Verify project visibility filtering
- [ ] Test team member management
- [ ] Verify client can view their projects
- [ ] Test inspector can access assigned projects

## Usage Examples

### Creating a Project with Full Team
```php
// Project Manager creates project
1. Select Client: "ABC Construction Ltd"
2. Assign Manager: "John Doe" (Project Manager)
3. Assign Inspector: "Jane Smith" (Inspector)
4. Add Team: "Bob Worker", "Alice Contractor"
5. Result:
   - Bob joins as "Worker" (his system role)
   - Alice joins as "Contractor" (her system role)
```

### Access Control Example
```php
// Scenario: Project "Building A"
- Manager: John
- Inspector: Jane
- Team: Bob (Worker), Alice (Contractor with edit permission)

Access Results:
- John: ✅ View, ✅ Edit, ✅ Manage Team
- Jane: ✅ View, ❌ Edit, ❌ Manage Team  
- Bob: ✅ View, ❌ Edit, ❌ Manage Team
- Alice: ✅ View, ✅ Edit, ✅ Manage Team
- Client: ✅ View, ❌ Edit, ❌ Manage Team
- Random User: ❌ View, ❌ Edit, ❌ Manage Team
```

## Troubleshooting

**Issue: User can't see project they're assigned to**
- Check if user is in `project_user` table
- Verify user has `view projects` permission
- Check ProjectPolicy `view()` method

**Issue: Team member can edit when they shouldn't**
- Verify user doesn't have `edit projects` permission
- Check if user is accidentally set as project manager
- Review ProjectPolicy `update()` method

**Issue: Client can't see their project**
- Verify `client_id` is set on project
- Check if client has `user_id` set (for portal access)
- Ensure client user account exists and is active

## Conclusion

The enhanced project management system provides:
- ✅ Organized client management
- ✅ Clear role-based team structure
- ✅ Granular permission control
- ✅ Automatic role inheritance
- ✅ Secure access control
- ✅ Flexible team management

Users can only see and edit projects they have explicit permission to access, with different capabilities based on their role and permissions in the system.
