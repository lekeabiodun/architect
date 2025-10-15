# Project Architect

A comprehensive housing construction project management system built with Laravel, Livewire, TailwindCSS, and FluxUI.

## Features Implemented

### ✅ Core Functionality
- **Project Management**
  - Create, read, update, delete projects
  - Track project details (client, location, dates, budget)
  - Real-time progress calculation based on phase weights
  - Project status management (active, on hold, completed, cancelled)
  - Budget tracking with variance calculations

- **Phase Management**
  - Organize projects into phases (Foundation, Framing, Exterior, MEP, Interior, Final)
  - Custom weight percentages for each phase
  - Automatic progress calculation
  - Phase status tracking

- **Task Management**
  - Tasks nested under phases
  - **Click-to-toggle status** (pending → in-progress → completed)
  - Task weighting within phases
  - Task dependencies (predecessor/successor relationships)
  - Estimated vs actual costs and hours
  - Task assignment to users
  - Inspection status tracking

- **User Roles & Permissions**
  - Project Manager (full access)
  - Contractor (task management)
  - Client (view access)
  - Inspector (inspection marking)

- **Progress Tracking**
  - Weighted progress calculation at task, phase, and project levels
  - Real-time updates when tasks change status
  - Visual progress bars throughout the interface

### 📊 Dashboard
- Statistics overview (active projects, total tasks, my tasks, completed)
- Recent projects table with progress indicators
- Quick navigation to projects

### 🎨 UI/UX Features
- Modern, responsive design with FluxUI components
- Dark mode support
- Search and filtering on projects list
- Grid and table layouts
- Modal-based forms for create/edit operations
- Dropdown menus for actions
- Status badges with color coding
- Progress bars with smooth animations

## Database Structure

### Tables Created
1. **users** (with role field)
2. **projects** (with soft deletes)
3. **phases**
4. **tasks** (with dependencies)
5. **project_user** (pivot table)
6. **task_comments**
7. **documents** (polymorphic)

### Key Relationships
- User hasMany Projects (as manager)
- User belongsToMany Projects (team members)
- Project hasMany Phases
- Phase hasMany Tasks
- Task belongsTo User (assignee)
- Task belongsTo Task (predecessor)

## Installation & Setup

### Run Migrations and Seed Data
```bash
php artisan migrate:fresh --seed
```

This will create:
- 4 test users with different roles
- 2 sample projects
- Multiple phases and tasks with realistic data

### Test Users
- **Project Manager**: manager@example.com / password
- **Contractor 1**: contractor1@example.com / password
- **Contractor 2**: contractor2@example.com / password
- **Client**: client@example.com / password

### Start Development Server
```bash
composer run dev
```

This will start:
- PHP development server
- Queue worker
- Log viewer (Pail)
- Vite dev server for assets

Or manually:
```bash
php artisan serve
npm run dev
```

## Key Features to Test

1. **Dashboard** (`/`)
   - View project statistics
   - See recent projects
   - Navigate to projects list

2. **Projects List** (`/projects`)
   - Create new project
   - Search and filter projects
   - Edit/delete projects
   - View project cards with progress

3. **Project Details** (`/projects/{id}`)
   - View project information
   - Add/edit/delete phases
   - Add/edit/delete tasks
   - **Click circles** to toggle task status
   - Watch progress update automatically
   - Assign tasks to users
   - Set task dependencies

## Technical Highlights

### Models with Business Logic
- `Project::calculateProgress()` - Weighted progress across phases
- `Phase::updateProgress()` - Cascading updates to project
- `Task::toggleStatus()` - Status cycle with automatic date tracking
- `Task::canStart()` - Dependency checking

### Livewire Components
- **Dashboard** - Stats and recent projects
- **Project\Index** - Projects list with CRUD operations
- **Project\Show** - Detailed project view with phases and tasks

### Progress Calculation
Progress is automatically calculated using weighted averages:
- Tasks contribute to phase progress (weighted by task weight)
- Phases contribute to project progress (weighted by phase weight)
- Updates cascade automatically when task status changes

### Click-to-Toggle Tasks
Tasks have visual status indicators:
- ⚪ **Pending** - Empty gray circle
- 🔵 **In Progress** - Blue circle with dot
- ✅ **Completed** - Green circle with checkmark

Click any circle to cycle through statuses!

## Future Enhancements (Not Yet Implemented)

Based on the specifications in `details.md`, these features can be added:

- **Timeline & Scheduling**
  - Gantt chart view
  - Milestone tracking
  - Schedule variance reports

- **Documentation & Compliance**
  - Photo uploads (before/after)
  - Document attachments
  - Inspection checklists
  - Permit tracking

- **Communication**
  - Task comments (model ready)
  - Status update notifications
  - Stakeholder communication log

- **Reporting & Analytics**
  - Project health dashboard
  - Cost variance reports
  - Completion forecasting
  - Historical data analysis

- **Advanced Features**
  - Critical path analysis
  - Resource scheduling
  - Worker availability tracking
  - Quality control workflows
  - Material cost tracking

## Code Structure

```
app/
├── Livewire/
│   ├── Dashboard.php
│   └── Project/
│       ├── Index.php
│       └── Show.php
├── Models/
│   ├── Document.php
│   ├── Phase.php
│   ├── Project.php
│   ├── Task.php
│   ├── TaskComment.php
│   └── User.php
database/
├── migrations/
│   ├── 2025_10_09_000001_add_role_to_users_table.php
│   ├── 2025_10_09_000002_create_projects_table.php
│   ├── 2025_10_09_000003_create_phases_table.php
│   ├── 2025_10_09_000004_create_tasks_table.php
│   ├── 2025_10_09_000005_create_project_user_table.php
│   ├── 2025_10_09_000006_create_task_comments_table.php
│   └── 2025_10_09_000007_create_documents_table.php
└── seeders/
    └── ProjectSeeder.php
resources/views/livewire/
├── dashboard.blade.php
└── project/
    ├── index.blade.php
    └── show.blade.php
```

## Technologies Used

- **Laravel 12** - PHP Framework
- **Livewire 3** - Full-stack framework for Laravel
- **Livewire Volt** - Single-file Livewire components
- **FluxUI** - Premium UI component library
- **TailwindCSS** - Utility-first CSS framework
- **Laravel Fortify** - Authentication backend
- **Pest** - Testing framework

## License

MIT
