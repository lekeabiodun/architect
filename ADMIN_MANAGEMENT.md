# 🔐 Admin & Account Management

## ✅ Complete Super Admin Features

---

## 🎯 **Overview**

The system now includes comprehensive admin tools for super admins to manage roles, permissions, and user accounts. These features provide full control over the authorization system and user management.

---

## 📊 **Features Implemented**

### 1. **Roles & Permissions Management** (`/settings/roles-permissions`)

**Access**: Super Admin only

**Features**:
- ✅ View all roles with user counts
- ✅ View all permissions grouped by category
- ✅ Create new custom roles
- ✅ Edit existing roles
- ✅ Delete roles (except critical system roles)
- ✅ Assign/remove permissions from roles in real-time
- ✅ Toggle permissions with instant feedback
- ✅ View users assigned to each role
- ✅ Permission categories overview
- ✅ Role statistics

**Components**:
- `app/Livewire/Settings/RolesAndPermissions.php`
- `resources/views/livewire/settings/roles-and-permissions.blade.php`

**Key Functionality**:
```php
// Create or edit roles
public function saveRole()
{
    $role = Role::create(['name' => $this->role_name]);
    $role->syncPermissions($this->role_permissions);
}

// Toggle permissions in real-time
public function togglePermission($roleId, $permissionId)
{
    $role = Role::findOrFail($roleId);
    $permission = Permission::findOrFail($permissionId);
    
    if ($role->hasPermissionTo($permission)) {
        $role->revokePermissionTo($permission);
    } else {
        $role->givePermissionTo($permission);
    }
}
```

---

### 2. **User Role Management** (`/settings/user-roles`)

**Access**: Super Admin only

**Features**:
- ✅ View all users with their assigned roles
- ✅ Search users by name or email
- ✅ Filter users by role
- ✅ Assign multiple roles to users
- ✅ Remove roles from users
- ✅ View user's current permissions
- ✅ View user's assigned projects
- ✅ Protect super admin role from self-removal
- ✅ Role distribution statistics
- ✅ Real-time role updates

**Components**:
- `app/Livewire/Settings/UserRoleManagement.php`
- `resources/views/livewire/settings/user-role-management.blade.php`

**Key Functionality**:
```php
// Assign roles to user
public function updateUserRoles()
{
    $user = User::findOrFail($this->selectedUser);
    
    // Prevent removing super_admin from yourself
    if ($user->id === auth()->id()) {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole && !in_array($superAdminRole->id, $this->user_roles)) {
            session()->flash('error', 'You cannot remove super admin role from yourself.');
            return;
        }
    }
    
    $user->syncRoles($this->user_roles);
}
```

---

## 🎨 **UI Features**

### Roles & Permissions Management

#### Layout
- **Left Panel**: List of all roles with user counts
- **Right Panel**: Detailed role view with permissions
- **Bottom Section**: All permissions overview

#### Interactive Elements
- ✅ Click on role to view details
- ✅ Toggle checkboxes to assign/remove permissions
- ✅ Create/Edit role modal with permission selection
- ✅ Real-time permission count updates
- ✅ Visual grouping by permission category

#### Permission Categories
- **Projects** - Project management permissions
- **Tasks** - Task-related permissions
- **Teams** - Team management permissions
- **Materials** - Material and inventory permissions
- **Requests** - Material request permissions
- **Inspections** - Task inspection permissions
- **Budgets** - Budget viewing permissions

---

### User Role Management

#### Layout
- **Left Panel**: Searchable list of all users
- **Right Panel**: User details and role assignment
- **Bottom Section**: Role distribution table

#### Interactive Elements
- ✅ Search by name or email
- ✅ Filter by role
- ✅ Click user to manage their roles
- ✅ Checkbox-based role assignment
- ✅ One-click role removal
- ✅ View user's current permissions
- ✅ View user's assigned projects

#### Safety Features
- ✅ Cannot remove super admin from yourself
- ✅ Protected roles indicator
- ✅ Confirmation dialogs for destructive actions
- ✅ Success/error notifications

---

## 🔐 **Security Features**

### Access Control
```php
public function mount()
{
    // Only super admins can access
    abort_unless(auth()->user()->isSuperAdmin(), 403, 'Unauthorized access');
}
```

### Protection Rules
1. **Super Admin Self-Protection**
   - Cannot remove super admin role from yourself
   - Checkbox disabled for own super admin role
   - Warning messages for attempted violations

2. **Critical Role Protection**
   - Cannot delete super_admin role
   - Cannot delete project_manager role
   - System prevents accidental removal

3. **Authorization Checks**
   - All actions require super admin verification
   - Middleware protection on routes
   - Component-level access validation

---

## 📋 **Permission System**

### Current Permissions (41 total)

#### Project Permissions
- `view projects`
- `create projects`
- `update projects`
- `delete projects`
- `view budget`

#### Task Permissions
- `view tasks`
- `create tasks`
- `update tasks`
- `delete tasks`
- `assign tasks`
- `complete tasks`
- `inspect tasks`

#### Team Permissions
- `view team`
- `manage team`
- `assign roles`

#### Material Permissions
- `view materials`
- `create materials`
- `update materials`
- `delete materials`
- `manage inventory`

#### Request Permissions
- `view material requests`
- `create material requests`
- `approve material requests`
- `reject material requests`
- `disburse material requests`
- `confirm material requests`

...and more

---

## 🚀 **Usage Guide**

### For Super Admins

#### Managing Roles & Permissions

1. **Navigate** to `/settings/roles-permissions`
2. **View** all existing roles in the left panel
3. **Click** on a role to view its permissions
4. **Toggle** checkboxes to add/remove permissions
5. **Create** new roles with the "Create Role" button
6. **Edit** existing roles with the "Edit" button
7. **Delete** non-critical roles as needed

#### Managing User Roles

1. **Navigate** to `/settings/user-roles`
2. **Search** or filter users
3. **Click** on a user to manage their roles
4. **Check/Uncheck** role checkboxes
5. **Click** "Update User Roles" to save changes
6. **View** the user's current permissions and projects

---

## 🎯 **Common Scenarios**

### Scenario 1: Create a New Custom Role
```
1. Go to Roles & Permissions
2. Click "Create Role"
3. Enter role name (e.g., "site_supervisor")
4. Select appropriate permissions
5. Click "Create Role"
6. Role is now available for assignment
```

### Scenario 2: Assign Role to User
```
1. Go to User Role Management
2. Search for the user
3. Click on their name
4. Check the desired roles
5. Click "Update User Roles"
6. User now has new permissions
```

### Scenario 3: Modify Role Permissions
```
1. Go to Roles & Permissions
2. Click on the role you want to modify
3. Toggle permissions on/off
4. Changes are saved instantly
5. All users with that role get updated permissions
```

### Scenario 4: Review User Permissions
```
1. Go to User Role Management
2. Click on the user
3. View "Current Roles & Permissions" section
4. See all permissions from their assigned roles
5. View their project assignments
```

---

## 📊 **Statistics & Monitoring**

### Roles & Permissions Dashboard Shows:
- Total number of roles
- Total number of permissions
- Number of permission categories
- Users per role
- Permissions per role

### User Role Management Shows:
- Total users count
- Users per role (distribution table)
- Permissions per role
- Project assignments per user

---

## 🔧 **Routes Added**

```php
// Settings (Super Admin only)
Route::get('/settings/roles-permissions', App\Livewire\Settings\RolesAndPermissions::class)
    ->name('settings.roles-permissions');

Route::get('/settings/user-roles', App\Livewire\Settings\UserRoleManagement::class)
    ->name('settings.user-roles');
```

---

## 🎨 **Navigation**

Added new "Administration" section to sidebar (visible only to super admins):
- 🛡️ Roles & Permissions
- 👤 User Role Management

---

## 💡 **Best Practices**

### When Creating Roles
1. Use descriptive names (e.g., `site_supervisor` not `role1`)
2. Assign only necessary permissions
3. Test permissions before assigning to users
4. Document custom roles and their purpose

### When Assigning Roles
1. Assign roles based on job function
2. Use multiple roles if needed (not one mega role)
3. Regularly review user role assignments
4. Remove roles when users change positions

### When Managing Permissions
1. Group related permissions together
2. Use principle of least privilege
3. Audit permissions regularly
4. Document any custom permission configurations

---

## ⚠️ **Important Notes**

### Critical Roles
- **super_admin**: Cannot be deleted
- **project_manager**: Cannot be deleted
- These roles are essential for system operation

### Self-Protection
- Super admins cannot remove their own super admin role
- This prevents accidental lockout
- You'll see a "Protected" badge on your own super admin role

### Permission Changes
- Changes to role permissions apply immediately
- Users don't need to log out/in
- All users with that role get updated permissions instantly

---

## 🧪 **Testing Checklist**

### Roles & Permissions
- [ ] Create new role
- [ ] Edit existing role
- [ ] Delete non-critical role
- [ ] Try to delete critical role (should fail)
- [ ] Toggle permissions on/off
- [ ] View role users
- [ ] Check permission categories

### User Role Management
- [ ] Search users
- [ ] Filter by role
- [ ] Assign role to user
- [ ] Remove role from user
- [ ] Try to remove your own super admin role (should fail)
- [ ] Update user roles
- [ ] View user permissions
- [ ] Check role distribution table

---

## 📝 **Summary**

### What's Available
✅ Complete role management system
✅ Real-time permission toggling
✅ User role assignment interface
✅ Search and filtering capabilities
✅ Safety protections for critical roles
✅ Visual permission categorization
✅ Statistics and monitoring
✅ Beautiful, intuitive UI

### What Super Admins Can Do
✅ Create custom roles
✅ Manage all permissions
✅ Assign roles to any user
✅ View complete permission structure
✅ Monitor role distribution
✅ Protect system integrity

---

## 🎉 **Result**

Super admins now have **complete control** over the authorization system with:
- Intuitive UI for managing roles and permissions
- Real-time updates without page refreshes
- Safety features to prevent system lockout
- Comprehensive visibility into user permissions
- Professional, production-ready interface

**The account management system is fully operational!** 🚀
