<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\InvitationData;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InviteUserNotification;
use Illuminate\Support\Facades\Notification;

class SendInvitationAction
{
    /**
     * Send an invitation to join a company.
     */
    public function execute(Company $company, User $invitedBy, InvitationData $data): Invitation
    {
        // Cancel existing invitation for this email if exists
        Invitation::where('company_id', $company->id)
            ->where('email', $data->email)
            ->whereNull('accepted_at')
            ->delete();

        $invitation = Invitation::create([
            'company_id' => $company->id,
            'email' => $data->email,
            'role' => $data->role->value,
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addHours(48),
            'invited_by' => $invitedBy->id,
        ]);

        // Send invitation email
        Notification::route('mail', $data->email)
            ->notify(new InviteUserNotification($invitation));

        return $invitation;
    }
}
