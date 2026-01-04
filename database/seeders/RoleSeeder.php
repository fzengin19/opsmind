<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create global permissions (not team-scoped)
        // Roles will be created per-company in CreateCompanyAction
        $permissions = [
            // Company
            'company.manage',

            // Users
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.invite',

            // Contacts
            'contact.view',
            'contact.create',
            'contact.update',
            'contact.delete',

            // Appointments
            'appointment.view',
            'appointment.create',
            'appointment.update',
            'appointment.delete',

            // Tasks
            'task.view',
            'task.create',
            'task.update',
            'task.delete',
            'task.assign',

            // Roles
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Note: Roles are created per-company in CreateCompanyAction::createDefaultRoles()
        // No global roles are needed with teams feature enabled
    }
}
