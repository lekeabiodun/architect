# Project Architect

Project Architect is a housing building progress tracking project management system.
This will be built using laravel, livewire, tailwindcss, fluxui.

## Core Functionality:

- Projects with full details (name, client, dates, budget)
- Phases (Foundation, Framing, Exterior, MEP, Interior, Final) with custom weights
- Tasks under each phase with multiple attributes
- Progress tracking - overall project and per-phase
- Task status (pending, in-progress, completed) - click the circles to toggle

1. Project Hierarchy & Phases

Break projects into phases (Foundation, Framing, Roofing, Electrical, Plumbing, Finishing, etc.)
Tasks should be grouped under phases
Some phases depend on others (can't do roofing before framing)

2. Task Dependencies

Predecessor/successor relationships (Task B can't start until Task A is done)
This prevents marking tasks complete out of sequence
Critical path tracking

3. Progress Weighting

Not all tasks are equal - foundation might be 15% of project, painting might be 3%
Allow custom weight/percentage per task
Or auto-calculate based on estimated hours/cost

4. Timeline & Scheduling

Planned start/end dates per task and project
Actual start/end dates
Milestones and deadlines
Gantt chart view

5. Resources & Assignments

Assign contractors/workers to tasks
Track who's responsible for what
Worker availability and scheduling

6. Budget Tracking

Estimated vs actual costs per task
Material costs
Labor costs
Budget alerts/overruns

7. Documentation & Compliance

Photo uploads (before/after)
Document attachments (permits, inspections, blueprints)
Inspection checklists
Permit tracking and approval status

8. Quality Control

Inspection status per task (Pending, Passed, Failed, Re-inspection)
Quality notes and issues
Rework tracking

9. Communication

Comments/notes per task
Status updates and notifications
Stakeholder communication log

10. Reporting & Analytics

Overall project health dashboard
Schedule variance (ahead/behind)
Cost variance
Completion forecasting
Historical data for future estimates

11. User Roles & Permissions

Project Manager (full access)
Contractors (update assigned tasks)
Clients (view-only or limited updates)
Inspectors (mark inspections)