<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AcceptInvitationAction
{
    /**
     * Accept an invitation and add user to company.
     * Refreshes session to update user relationships.
     */
    public function execute(Invitation $invitation, User $user): User
    {
        // Add user to company with the invited role
        $invitation->company->addUser($user, $invitation->getRoleName());

        // Mark invitation as accepted
        $invitation->update(['accepted_at' => now()]);

        // Refresh user from database to get updated relationships
        $freshUser = $user->fresh();

        // Update authenticated session with fresh user model
        Auth::setUser($freshUser);

        return $freshUser;
    }
}
