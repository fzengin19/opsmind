<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('User Model', function () {
    it('can be created with factory', function () {
        $user = User::factory()->create();

        expect($user)
            ->toBeInstanceOf(User::class)
            ->id->toBeInt()
            ->name->toBeString()
            ->email->toBeString();
    });

    it('belongs to company', function () {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create();

        expect($user->company->id)->toBe($company->id);
    });

    it('has initials method', function () {
        $user = User::factory()->create(['name' => 'John Doe']);

        expect($user->initials())->toBe('JD');
    });

    it('handles single name for initials', function () {
        $user = User::factory()->create(['name' => 'John']);

        expect($user->initials())->toBe('J');
    });

    it('can have roles assigned', function () {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($user->hasRole('admin'))->toBeTrue();
    });
});
