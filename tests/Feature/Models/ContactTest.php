<?php

declare(strict_types=1);

use App\Enums\ContactType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Contact Model', function () {
    it('can be created with factory', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($contact)
            ->toBeInstanceOf(Contact::class)
            ->id->toBeInt()
            ->first_name->toBeString()
            ->last_name->toBeString();
    });

    it('casts type to ContactType enum', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $contact = Contact::factory()->customer()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($contact->type)
            ->toBeInstanceOf(ContactType::class)
            ->toBe(ContactType::Customer);
    });

    it('has fullName attribute', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        expect($contact->full_name)->toBe('John Doe');
    });

    it('casts tags to array', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'tags' => ['VIP', 'Important'],
        ]);

        expect($contact->tags)
            ->toBeArray()
            ->toContain('VIP', 'Important');
    });

    it('belongs to company', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($contact->company->id)->toBe($company->id);
    });

    it('belongs to created_by user', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($contact->createdBy->id)->toBe($user->id);
    });
});
