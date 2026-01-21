<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    /**
     * Determine whether the user can view any contacts.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentCompany() !== null;
    }

    /**
     * Determine whether the user can view the contact.
     */
    public function view(User $user, Contact $contact): bool
    {
        return $contact->company_id === $user->currentCompany()?->id;
    }

    /**
     * Determine whether the user can create contacts.
     */
    public function create(User $user): bool
    {
        return $user->currentCompany() !== null;
    }

    /**
     * Determine whether the user can update the contact.
     */
    public function update(User $user, Contact $contact): bool
    {
        if ($contact->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        setPermissionsTeamId($user->currentCompany()->id);

        return $contact->created_by === $user->id
            || $user->hasPermissionTo(PermissionEnum::ContactUpdate->value);
    }

    /**
     * Determine whether the user can delete the contact.
     */
    public function delete(User $user, Contact $contact): bool
    {
        if ($contact->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        setPermissionsTeamId($user->currentCompany()->id);

        return $contact->created_by === $user->id
            || $user->hasPermissionTo(PermissionEnum::ContactDelete->value);
    }
}
