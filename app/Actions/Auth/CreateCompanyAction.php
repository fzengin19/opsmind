<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateCompanyAction
{
    /**
     * Create a new company and assign the user as owner.
     */
    public function execute(User $user, string $name): Company
    {
        $company = Company::create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'timezone' => $user->timezone ?? 'Europe/Istanbul',
            'settings' => [
                'language' => 'tr',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
            ],
        ]);

        // Create default roles for this company
        $this->createDefaultRoles($company);

        // Add user as owner
        $company->addUser($user, 'owner');

        return $company;
    }

    /**
     * Create default roles for a company.
     */
    private function createDefaultRoles(Company $company): void
    {
        setPermissionsTeamId($company->id);

        // Owner - All permissions, cannot be deleted
        $owner = Role::create([
            'name' => 'owner',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);
        $owner->givePermissionTo(Permission::all());

        // Admin - All permissions
        $admin = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);
        $admin->givePermissionTo(Permission::all());

        // Member - Basic permissions
        $member = Role::create([
            'name' => 'member',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);
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
