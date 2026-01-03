<?php

declare(strict_types=1);

use App\Enums\CompanyRole;
use App\Enums\ContactType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Contact Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->company->addUser($this->user, CompanyRole::Owner);
    });

    it('can be created with factory', function () {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($contact)
            ->toBeInstanceOf(Contact::class)
            ->id->toBeInt()
            ->first_name->toBeString()
            ->last_name->toBeString();
    });

    it('casts type to ContactType enum', function () {
        $contact = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($contact->type)
            ->toBeInstanceOf(ContactType::class)
            ->toBe(ContactType::Customer);
    });

    it('has fullName attribute', function () {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        expect($contact->full_name)->toBe('John Doe');
    });

    it('casts tags to array', function () {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'tags' => ['VIP', 'Important'],
        ]);

        expect($contact->tags)
            ->toBeArray()
            ->toContain('VIP', 'Important');
    });

    it('belongs to company', function () {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($contact->company->id)->toBe($this->company->id);
    });

    it('belongs to created_by user', function () {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($contact->createdBy->id)->toBe($this->user->id);
    });
});
