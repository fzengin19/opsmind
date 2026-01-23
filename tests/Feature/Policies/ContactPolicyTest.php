<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('ContactPolicy', function () {
    it('allows any company user to view contacts', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('viewAny', Contact::class))->toBeTrue();
    });

    it('denies user without company to view contacts', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        expect($user->can('viewAny', Contact::class))->toBeFalse();
    });

    it('allows viewing contact from same company', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('view', $contact))->toBeTrue();
    });

    it('denies viewing contact from different company', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        createDefaultRolesForCompany($company1);
        createDefaultRolesForCompany($company2);

        $user = User::factory()->create();
        $company1->addUser($user, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company2->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company1->id);

        expect($user->can('view', $contact))->toBeFalse();
    });

    it('allows any company user to create contacts', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('create', Contact::class))->toBeTrue();
    });

    it('allows creator to update contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('update', $contact))->toBeTrue();
    });

    it('allows user with permission to update contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        setPermissionsTeamId($company->id);
        $otherUser->givePermissionTo(PermissionEnum::ContactUpdate->value);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('update', $contact))->toBeTrue();
    });

    it('denies user without permission from updating others contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('update', $contact))->toBeFalse();
    });

    it('allows creator to delete contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('delete', $contact))->toBeTrue();
    });

    it('allows user with permission to delete contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        setPermissionsTeamId($company->id);
        $otherUser->givePermissionTo(PermissionEnum::ContactDelete->value);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('delete', $contact))->toBeTrue();
    });

    it('denies user without permission from deleting others contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('delete', $contact))->toBeFalse();
    });
});
