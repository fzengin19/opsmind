<?php

use App\Actions\Auth\SendInvitationAction;
use App\Data\InvitationData;
use App\Enums\CompanyRole;
use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[\Livewire\Attributes\Layout('components.layouts.app')] class extends Component {
    public string $inviteEmail = '';
    public string $inviteRole = 'member';
    public bool $showInviteModal = false;

    public function rules(): array
    {
        return [
            'inviteEmail' => ['required', 'email'],
            'inviteRole' => ['required', 'in:admin,manager,member'],
        ];
    }

    public function getCompanyProperty()
    {
        return Auth::user()->currentCompany();
    }

    public function getUsersProperty()
    {
        return $this->company->users()->with('roles')->get();
    }

    public function getPendingInvitationsProperty()
    {
        return Invitation::where('company_id', $this->company->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get();
    }

    public function canManageTeam(): bool
    {
        $role = Auth::user()->roleIn($this->company);

        return $role && $role->canManageTeam();
    }

    public function sendInvitation(SendInvitationAction $action): void
    {
        if (! $this->canManageTeam()) {
            return;
        }

        $this->validate();

        $data = new InvitationData(
            email: $this->inviteEmail,
            role: CompanyRole::from($this->inviteRole),
        );

        $action->execute($this->company, Auth::user(), $data);

        $this->inviteEmail = '';
        $this->inviteRole = 'member';
        $this->showInviteModal = false;

        $this->dispatch('invitation-sent');
    }

    public function updateRole(int $userId, string $role): void
    {
        if (! $this->canManageTeam()) {
            return;
        }

        $this->company->users()->updateExistingPivot($userId, [
            'role' => $role,
        ]);
    }

    public function removeUser(int $userId): void
    {
        if (! $this->canManageTeam()) {
            return;
        }

        // Cannot remove yourself
        if ($userId === Auth::id()) {
            return;
        }

        // Cannot remove the last owner
        if ($this->company->owners()->count() === 1) {
            $userRole = $this->company->users()->where('user_id', $userId)->first()?->pivot?->role;
            if ($userRole === 'owner') {
                return;
            }
        }

        $this->company->users()->detach($userId);
    }

    public function cancelInvitation(int $invitationId): void
    {
        if (! $this->canManageTeam()) {
            return;
        }

        Invitation::where('id', $invitationId)
            ->where('company_id', $this->company->id)
            ->delete();
    }
}; ?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('team.title') }}</flux:heading>
                <flux:text class="text-zinc-500">{{ $this->company->name }} {{ __('team.members') }}</flux:text>
            </div>

            @if ($this->canManageTeam())
                <flux:button variant="primary" wire:click="$set('showInviteModal', true)">
                    {{ __('team.invite_button') }}
                </flux:button>
            @endif
        </div>

        <!-- Team Members -->
        <div class="overflow-hidden border rounded-xl border-zinc-200 dark:border-zinc-700">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-sm font-medium text-left text-zinc-700 dark:text-zinc-300">
                            {{ __('team.member') }}
                        </th>
                        <th class="px-4 py-3 text-sm font-medium text-left text-zinc-700 dark:text-zinc-300">
                            {{ __('common.role') }}
                        </th>
                        <th class="px-4 py-3 text-sm font-medium text-left text-zinc-700 dark:text-zinc-300">
                            {{ __('team.joined_at') }}
                        </th>
                        @if ($this->canManageTeam())
                            <th class="px-4 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$user->name" />
                                    <div>
                                        <div class="font-medium">{{ $user->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php $userRole = \App\Enums\CompanyRole::from($user->pivot->role); @endphp
                                <flux:badge :color="$userRole->color()">{{ $userRole->label() }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500">
                                {{ $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('d.m.Y') : '-' }}
                            </td>
                            @if ($this->canManageTeam())
                                <td class="px-4 py-3 text-right">
                                    @if ($user->id !== auth()->id())
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                            <flux:menu>
                                                <flux:menu.item wire:click="updateRole({{ $user->id }}, 'admin')">
                                                    {{ __('team.make_admin') }}
                                                </flux:menu.item>
                                                <flux:menu.item wire:click="updateRole({{ $user->id }}, 'manager')">
                                                    {{ __('team.make_manager') }}
                                                </flux:menu.item>
                                                <flux:menu.item wire:click="updateRole({{ $user->id }}, 'member')">
                                                    {{ __('team.make_member') }}
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger"
                                                    wire:click="removeUser({{ $user->id }})"
                                                    wire:confirm="{{ __('team.remove_confirm') }}">
                                                    {{ __('team.remove_from_team') }}
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pending Invitations -->
        @if ($this->canManageTeam() && $this->pendingInvitations->count() > 0)
            <div>
                <flux:heading size="lg" class="mb-3">{{ __('team.pending_invitations') }}</flux:heading>
                <div class="overflow-hidden border rounded-xl border-zinc-200 dark:border-zinc-700">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-sm font-medium text-left text-zinc-700 dark:text-zinc-300">
                                    {{ __('common.email') }}
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-zinc-700 dark:text-zinc-300">
                                    {{ __('common.role') }}
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-zinc-700 dark:text-zinc-300">
                                    {{ __('team.validity') }}
                                </th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($this->pendingInvitations as $invitation)
                                <tr wire:key="invitation-{{ $invitation->id }}">
                                    <td class="px-4 py-3">{{ $invitation->email }}</td>
                                    <td class="px-4 py-3">
                                        <flux:badge :color="$invitation->getCompanyRole()->color()">
                                            {{ $invitation->getCompanyRole()->label() }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-500">
                                        {{ $invitation->expires_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:button variant="ghost" size="sm"
                                            wire:click="cancelInvitation({{ $invitation->id }})">
                                            {{ __('common.cancel') }}
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Invite Modal -->
    <flux:modal wire:model="showInviteModal" class="max-w-md">
        <div class="flex flex-col gap-6">
            <flux:heading size="lg">{{ __('team.invite_modal_title') }}</flux:heading>

            <form wire:submit="sendInvitation" class="flex flex-col gap-4">
                <flux:input wire:model="inviteEmail" :label="__('team.invite_email')" type="email" required
                    :placeholder="__('team.invite_email_placeholder')" />

                <flux:select wire:model="inviteRole" :label="__('team.invite_role')">
                    <option value="admin">{{ __('team.roles.admin') }}</option>
                    <option value="manager">{{ __('team.roles.manager') }}</option>
                    <option value="member">{{ __('team.roles.member') }}</option>
                </flux:select>

                <div class="flex justify-end gap-2 mt-4">
                    <flux:button variant="ghost" wire:click="$set('showInviteModal', false)">
                        {{ __('common.cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('team.invite_button') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>