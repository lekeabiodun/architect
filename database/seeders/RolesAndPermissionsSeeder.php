<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Project permissions
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',

            // Task permissions
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',
            'complete tasks',

            // Inspection permissions
            'inspect tasks',
            'approve inspections',
            'fail inspections',

            // Material permissions
            'view materials',
            'manage materials',
            'create material requests',
            'approve material requests',
            'disburse materials',
            'confirm material delivery',

            // Inventory permissions
            'view inventory',
            'manage inventory',
            'allocate materials',

            // Budget permissions
            'view budget',
            'manage budget',

            // Bill of Quantities permissions
            'view boq',
            'create boq',
            'edit boq',
            'delete boq',

            // Team permissions
            'manage team members',
            'view team members',

            // Document permissions
            'upload documents',
            'view documents',
            'delete documents',

            // Comment permissions
            'add comments',
            'view comments',

            // Time tracking permissions
            'clock in',
            'clock out',
            'view time entries',
            'manage time entries',
            'view leave requests',
            'create leave requests',
            'edit leave requests',
            'approve leave requests',
            'reject leave requests',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // 2. Project Manager - Manage assigned projects
        $projectManager = Role::firstOrCreate(['name' => 'project_manager']);
        $projectManager->givePermissionTo([
            'view projects',
            'edit projects',
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',
            'view materials',
            'manage materials',
            'view inventory',
            'manage inventory',
            'allocate materials',
            'approve material requests',
            'disburse materials',
            'view budget',
            'manage budget',
            'view boq',
            'create boq',
            'edit boq',
            'delete boq',
            'view team members',
            'upload documents',
            'view documents',
            'add comments',
            'view comments',
        ]);

        // 3. Contractor - Work on assigned projects
        $contractor = Role::firstOrCreate(['name' => 'contractor']);
        $contractor->givePermissionTo([
            'view projects',
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            'complete tasks',
            'view materials',
            'manage materials',
            'view inventory',
            'manage inventory',
            'create material requests',
            'view budget',
            'view boq',
            'upload documents',
            'view documents',
            'add comments',
            'view comments',
        ]);

        // 4. Inspector - Inspect tasks and confirm materials
        $inspector = Role::firstOrCreate(['name' => 'inspector']);
        $inspector->givePermissionTo([
            'view projects',
            'view tasks',
            'inspect tasks',
            'approve inspections',
            'fail inspections',
            'view materials',
            'view inventory',
            'confirm material delivery',
            'view boq',
            'upload documents',
            'view documents',
            'add comments',
            'view comments',
        ]);

        // 5. Engineer - Technical work on projects
        $engineer = Role::firstOrCreate(['name' => 'engineer']);
        $engineer->givePermissionTo([
            'view projects',
            'view tasks',
            'edit tasks',
            'complete tasks',
            'view materials',
            'manage materials',
            'view inventory',
            'create material requests',
            'view budget',
            'view boq',
            'upload documents',
            'view documents',
            'add comments',
            'view comments',
        ]);

        // 6. Developer - Similar to engineer
        $developer = Role::firstOrCreate(['name' => 'developer']);
        $developer->givePermissionTo([
            'view projects',
            'view tasks',
            'edit tasks',
            'complete tasks',
            'view materials',
            'manage materials',
            'view inventory',
            'create material requests',
            'view budget',
            'view boq',
            'upload documents',
            'view documents',
            'add comments',
            'view comments',
        ]);

        // 7. Director - High-level oversight and approvals
        $director = Role::firstOrCreate(['name' => 'director']);
        $director->givePermissionTo([
            'view projects',
            'edit projects',
            'view tasks',
            'view materials',
            'manage materials',
            'view inventory',
            'manage inventory',
            'approve material requests',
            'disburse materials',
            'view budget',
            'manage budget',
            'view boq',
            'create boq',
            'edit boq',
            'delete boq',
            'view team members',
            'manage team members',
            'view documents',
            'view comments',
        ]);

        // 8. Manager - Operational management
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'view projects',
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            'view materials',
            'manage materials',
            'view inventory',
            'manage inventory',
            'approve material requests',
            'disburse materials',
            'view budget',
            'view boq',
            'create boq',
            'edit boq',
            'delete boq',
            'view team members',
            'upload documents',
            'view documents',
            'add comments',
            'view comments',
            'clock in',
            'clock out',
            'view time entries',
            'manage time entries',
            'view leave requests',
            'create leave requests',
            'edit leave requests',
            'approve leave requests',
            'reject leave requests',
        ]);

        // 9. Client - View only, no materials
        $client = Role::firstOrCreate(['name' => 'client']);
        $client->givePermissionTo([
            'view projects',
            'view tasks',
            'view documents',
            'add comments',
            'view comments',
        ]);

        // 10. Worker - Limited to assigned tasks only
        $worker = Role::firstOrCreate(['name' => 'worker']);
        $worker->givePermissionTo([
            'view tasks',
            'complete tasks',
            'upload documents',
            'view documents',
            'add comments',
            'view comments',
        ]);
    }
}
