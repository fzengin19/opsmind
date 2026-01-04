<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Invitation;
use App\Models\User;

class AcceptInvitationAction
{
    /**
     * Accept an invitation and add user to company.
     */
    public function execute(Invitation $invitation, User $user): void
    {
        // Add user to company with the invited role
        $invitation->company->addUser($user, $invitation->getCompanyRole());

        // Mark invitation as accepted
        $invitation->update(['accepted_at' => now()]);

        // Assign default member role if user has no global role
        if ($user->roles->isEmpty()) {
            $user->assignRole('member');
        }
    }
}
