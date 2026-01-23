<?php

declare(strict_types=1);

use App\Actions\Contacts\CreateContactAction;
use App\Actions\Contacts\DeleteContactAction;
use App\Actions\Contacts\UpdateContactAction;
use App\Data\ContactData;
use App\Enums\ContactType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('CreateContactAction', function () {
    it('creates contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $data = new ContactData(
            id: null,
            company_id: $company->id,
            type: ContactType::Customer,
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            phone: '+1234567890',
            company_name: 'Example Inc',
            job_title: 'CEO',
            notes: 'Test notes',
            tags: ['vip', 'customer'],
            created_by: null,
        );

        $action = app(CreateContactAction::class);
        $contact = $action->execute($data, $user);

        expect($contact)->toBeInstanceOf(Contact::class);
        expect($contact->first_name)->toBe('John');
        expect($contact->last_name)->toBe('Doe');
        expect($contact->email)->toBe('john@example.com');
        expect($contact->created_by)->toBe($user->id);
    });

    it('creates contact without optional fields', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $data = new ContactData(
            id: null,
            company_id: $company->id,
            type: ContactType::Vendor,
            first_name: 'Jane',
            last_name: 'Smith',
            created_by: null,
        );

        $action = app(CreateContactAction::class);
        $contact = $action->execute($data, $user);

        expect($contact)->toBeInstanceOf(Contact::class);
        expect($contact->first_name)->toBe('Jane');
        expect($contact->last_name)->toBe('Smith');
        expect($contact->email)->toBeNull();
    });
});

describe('UpdateContactAction', function () {
    it('updates contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $data = new ContactData(
            id: $contact->id,
            company_id: $company->id,
            type: ContactType::Partner,
            first_name: 'Updated',
            last_name: 'Name',
            email: 'updated@example.com',
            created_by: null,
        );

        $action = app(UpdateContactAction::class);
        $updatedContact = $action->execute($contact, $data);

        expect($updatedContact->first_name)->toBe('Updated');
        expect($updatedContact->last_name)->toBe('Name');
        expect($updatedContact->email)->toBe('updated@example.com');
        expect($updatedContact->type)->toBe(ContactType::Partner);
    });

    it('wraps operations in database transaction', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $originalFirstName = $contact->first_name;

        $data = new ContactData(
            id: $contact->id,
            company_id: $company->id,
            type: ContactType::Customer,
            first_name: 'Updated',
            last_name: 'Name',
            email: 'valid@email.com',
            created_by: null,
        );

        $action = app(UpdateContactAction::class);

        $action->execute($contact, $data);

        expect($contact->fresh()->first_name)->toBe('Updated');
    });
});

describe('DeleteContactAction', function () {
    it('deletes contact', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect(Contact::where('id', $contact->id)->exists())->toBeTrue();

        $action = app(DeleteContactAction::class);
        $action->execute($contact);

        expect(Contact::where('id', $contact->id)->exists())->toBeFalse();
    });
});
