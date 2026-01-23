<?php

declare(strict_types=1);

use App\Actions\Auth\AcceptInvitationAction;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('Invitation Accept - Session Refresh', function () {
    it('refreshes session when accepting invitation as logged in user', function () {
        // Setup: Create company and user
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        // Add user to company initially
        $company->addUser($user, 'member');
        expect($user->companies)->toHaveCount(1);

        // Simulate loaded relationships in session
        $user->load('companies');
        Auth::login($user);

        // Remove user from company (simulating the bug scenario)
        $company->removeUser($user);
        expect($user->fresh()->companies)->toHaveCount(0);

        // Create invitation
        $invitation = Invitation::create([
            'company_id' => $company->id,
            'email' => $user->email,
            'role_name' => 'admin',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addHours(48),
            'invited_by' => $user->id,
        ]);

        // Accept invitation
        $action = app(AcceptInvitationAction::class);
        $freshUser = $action->execute($invitation, $user);

        // Assert: Session user has updated relationships
        expect(Auth::user()->companies)->toHaveCount(1);
        expect(Auth::user()->currentCompany()->id)->toBe($company->id);
        expect($freshUser->currentCompany()->id)->toBe($company->id);
    });

    it('returns fresh user after accepting invitation', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $invitation = Invitation::create([
            'company_id' => $company->id,
            'email' => $user->email,
            'role_name' => 'member',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addHours(48),
            'invited_by' => $user->id,
        ]);

        $action = app(AcceptInvitationAction::class);
        $result = $action->execute($invitation, $user);

        // Assert: Returns fresh user instance
        expect($result)->toBeInstanceOf(User::class);
        expect($result->companies)->toHaveCount(1);
        expect($result->id)->toBe($user->id);
    });

    it('marks invitation as accepted', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $invitation = Invitation::create([
            'company_id' => $company->id,
            'email' => $user->email,
            'role_name' => 'member',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addHours(48),
            'invited_by' => $user->id,
        ]);

        expect($invitation->accepted_at)->toBeNull();

        $action = app(AcceptInvitationAction::class);
        $action->execute($invitation, $user);

        expect($invitation->fresh()->accepted_at)->not->toBeNull();
    });
});
