<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Volt;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamInvitationPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_admin()
    {
        // Setup
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $company->users()->attach($owner, ['joined_at' => now()]);
        
        // Setup Roles
        setPermissionsTeamId($company->id);
        $ownerRole = Role::create(['name' => 'owner', 'guard_name' => 'web', 'company_id' => $company->id]);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web', 'company_id' => $company->id]);
        
        $owner->assignRole('owner');
        
        $this->actingAs($owner);
        
        // Action
        Volt::test('team.index')
            ->set('inviteEmail', 'newadmin@example.com')
            ->set('inviteRole', 'admin')
            ->call('sendInvitation')
            ->assertHasNoErrors();
            
        // Assert
        $this->assertDatabaseHas('invitations', [
            'company_id' => $company->id,
            'email' => 'newadmin@example.com',
            'role_name' => 'admin',
        ]);
    }
}
