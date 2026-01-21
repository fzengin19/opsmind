<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create global permissions from PermissionEnum
        // Roles will be created per-company in CreateCompanyAction
        foreach (PermissionEnum::cases() as $permissionEnum) {
            Permission::firstOrCreate(['name' => $permissionEnum->value, 'guard_name' => 'web']);
        }

        // Note: Roles are created per-company in CreateCompanyAction::createDefaultRoles()
        // No global roles are needed with teams feature enabled
    }
}
