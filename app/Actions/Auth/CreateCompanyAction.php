<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;

class CreateCompanyAction
{
    /**
     * Create a new company and assign the user as owner.
     */
    public function execute(User $user, string $name): Company
    {
        $company = Company::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'timezone' => $user->timezone ?? 'Europe/Istanbul',
            'settings' => [
                'language' => 'tr',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
            ],
        ]);

        $company->addUser($user, CompanyRole::Owner);
        $user->assignRole('admin');

        return $company;
    }
}
