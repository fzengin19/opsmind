<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
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
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'user.view',
            'user.invite',
            'contact.view',
            'contact.create',
            'contact.update',
            'appointment.view',
            'appointment.create',
            'appointment.update',
            'task.view',
            'task.create',
            'task.update',
            'task.assign',
        ]);

        $member = Role::create(['name' => 'member']);
        $member->givePermissionTo([
            'contact.view',
            'contact.create',
            'appointment.view',
            'appointment.create',
            'task.view',
            'task.create',
            'task.update',
        ]);
    }
}
