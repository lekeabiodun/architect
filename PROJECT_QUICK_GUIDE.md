# Project Management - Quick Reference Guide

## Creating a Project

### Required Steps
1. Navigate to `/projects`
2. Click "Create Project"
3. Fill in:
   - ✅ Project Name
   - ✅ Client (select from dropdown OR enter name manually)
   - ✅ Project Manager (dropdown)
   - ⚪ Inspector (optional dropdown)
   - ⚪ Team Members (optional multi-select)
   - ⚪ Budget, dates, location, etc.
4. Click "Create Project"

### What Happens
- Project created with selected manager
- Inspector assigned (if selected)
- Team members added **with their system roles automatically**
- Everyone can now see the project (if they have access)

## Role Inheritance

When you add someone to a project:
- **Project Manager** joins as → "Project Manager"
- **Inspector** joins as → "Inspector"  
- **Contractor** joins as → "Contractor"
- **Worker** joins as → "Worker"
- **Client** joins as → "Client"

**You cannot change their role in the project - it comes from their system role!**

## Who Can See What?

### View Projects
| Role | What They See |
|------|--------------|
| Super Admin | ALL projects |
| Project Manager | Projects they manage OR are assigned to |
| Inspector | Projects they're assigned to inspect |
| Team Member | Only projects they're added to |
| Client | Only their own projects |
| Worker | ❌ Cannot see projects (tasks only) |

### Edit Projects
| Role | Can Edit? |
|------|-----------|
| Super Admin | ✅ All projects |
| Project Manager | ✅ Their projects only |
| Team Member with "edit projects" permission | ✅ Assigned projects only |
| Inspector | ❌ Can view & inspect only |
| Client | ❌ Can view only |
| Worker | ❌ No access |

### Manage Team Members
| Role | Can Add/Remove Team? |
|------|---------------------|
| Super Admin | ✅ All projects |
| Project Manager | ✅ Their projects |
| Team Member with "edit projects" permission | ✅ Assigned projects |
| Everyone else | ❌ No |

## Client Management

### Selecting a Client
- **Option 1:** Select existing client from dropdown
  - Client info auto-populates
  - Links to client record
  - Client user (if exists) can view project

- **Option 2:** Enter client name manually
  - No client record created
  - Just stores the name

### Client Viewing
- Clients see projects where `client_id` matches their client record
- Clients need a user account linked to view projects
- Clients can ONLY view, never edit

## Permission Rules

### I want to...

**Create a project**
- Need: `create projects` permission
- Available to: Admin, Project Managers

**Edit a project**
- Need: Be the manager OR have `edit projects` permission AND be on team
- Available to: Manager, Team members with permission

**Add team members**
- Need: Be the manager OR have `edit projects` permission AND be on team
- Available to: Manager, Team members with permission

**View budget**
- Need: Be on team AND have `view budget` permission
- NOT available to: Clients, Workers

**Inspect tasks**
- Need: Be assigned as inspector OR have `inspect tasks` permission
- Available to: Inspectors

## Common Scenarios

### Scenario 1: New Project for Existing Client
```
1. Create Project
2. Select "ABC Construction" from client dropdown
3. Assign Manager: John Doe
4. Assign Inspector: Jane Smith
5. Add Team: Bob, Alice
Result: Everyone can now access the project
```

### Scenario 2: Team Member Needs Edit Access
```
Problem: Bob is on the team but can't edit
Solution: 
  - Give Bob the "edit projects" permission in system
  - Bob can now edit this project and any future assigned projects
```

### Scenario 3: Client Wants to View Project
```
Option 1: Link client to user account
  - Create client record with user_id
  - Client user can now view projects
  
Option 2: Use project sharing
  - Add client user as team member
  - They see project (but can't edit)
```

## Key Features

✅ **Client Dropdown** - Select from existing clients
✅ **Auto-Role Assignment** - Team members get their system role
✅ **Permission-Based Editing** - Only authorized users can edit
✅ **Project Filtering** - Users see only their projects  
✅ **Inspector Assignment** - Dedicated inspector per project
✅ **Team Management** - Add/remove team members easily

## Important Notes

⚠️ **Team members inherit their system role** - You cannot assign custom roles per project

⚠️ **Edit permission is separate from view** - Being on the team doesn't mean you can edit

⚠️ **Clients are read-only** - They can never edit projects, only view

⚠️ **Workers don't see projects** - They only see their assigned tasks

⚠️ **Project manager has full control** - They can always edit and manage their projects

## Troubleshooting

**Q: I can't see a project I should have access to**
- Check if you're added to the project team
- Verify you have `view projects` permission
- Ask project manager to add you

**Q: I'm on the team but can't edit**
- You need `edit projects` permission in your system role
- OR you need to be the project manager
- Contact admin to grant permission

**Q: Client can't see their project**  
- Verify client record has `user_id` set
- Check project has correct `client_id`
- Ensure client user account is active

**Q: Inspector can't access project**
- Check if inspector is assigned to project (`inspector_id`)
- Verify inspector has `inspect tasks` permission
- Add inspector to project team as backup

## Setup Checklist

For new projects:
- [ ] Client selected or name entered
- [ ] Project manager assigned
- [ ] Inspector assigned (if needed)
- [ ] Team members added
- [ ] Budget and dates set
- [ ] Status set appropriately

For team management:
- [ ] Verify users have correct system roles
- [ ] Grant `edit projects` permission to users who need it
- [ ] Ensure inspectors have `inspect tasks` permission
- [ ] Add users to projects they need access to

## Quick Links

- **View Projects:** `/projects`
- **Create Project:** `/projects` → "Create Project" button
- **Edit Project:** Click project card → Edit button
- **Manage Team:** Edit project → Team Members section

## Testing Your Setup

1. **Create test project with yourself as manager**
2. **Add a team member and verify they can see it**
3. **Try to edit as team member** (should work if you have permission)
4. **Test with client user** (should see project, can't edit)
5. **Test with inspector** (should see project, can inspect)

## Support

If you encounter issues:
1. Check the full documentation: `PROJECT_MANAGEMENT_ENHANCEMENT.md`
2. Review permission matrix in documentation  
3. Verify database relationships are correct
4. Check Laravel logs for authorization errors
