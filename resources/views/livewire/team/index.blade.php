<?php

use App\Actions\Auth\SendInvitationAction;
use App\Data\InvitationData;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new #[\Livewire\Attributes\Layout('components.layouts.app')] class extends Component {
    public string $inviteEmail = '';
    public string $inviteRole = 'member';
    public bool $showInviteModal = false;

    public function rules(): array
    {
        return [
            'inviteEmail' => ['required', 'email'],
            'inviteRole' => ['required', 'in:admin,member'],
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

    public function getAvailableRolesProperty()
    {
        setPermissionsTeamId($this->company->id);
        return Role::where('company_id', $this->company->id)
            ->where('name', '!=', 'owner') // Owner cannot be assigned
            ->pluck('name');
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
        $user = Auth::user();
        setPermissionsTeamId($this->company->id);

        // Owner can do everything
        if ($user->hasRole('owner')) {
            return true;
        }

        return $user->hasPermissionTo('user.invite');
    }

    public function sendInvitation(SendInvitationAction $action): void
    {
        if (!$this->canManageTeam()) {
            return;
        }

        $this->validate();

        $data = new InvitationData(
            email: $this->inviteEmail,
            roleName: $this->inviteRole,
        );

        $action->execute($this->company, Auth::user(), $data);

        $this->inviteEmail = '';
        $this->inviteRole = 'member';
        $this->showInviteModal = false;

        $this->dispatch('invitation-sent');
    }

    public function updateRole(int $userId, string $roleName): void
    {
        if (!$this->canManageTeam()) {
            return;
        }

        // Cannot assign owner role
        if ($roleName === 'owner') {
            return;
        }

        $targetUser = User::find($userId);
        if (!$targetUser) {
            return;
        }

        // Cannot change owner's role
        setPermissionsTeamId($this->company->id);
        if ($targetUser->hasRole('owner')) {
            return;
        }

        $targetUser->syncRoles([$roleName]);
    }

    public function removeUser(int $userId): void
    {
        if (!$this->canManageTeam()) {
            return;
        }

        // Cannot remove yourself
        if ($userId === Auth::id()) {
            return;
        }

        $targetUser = User::find($userId);
        if (!$targetUser) {
            return;
        }

        // Cannot remove an owner
        setPermissionsTeamId($this->company->id);
        if ($targetUser->hasRole('owner')) {
            return;
        }

        $this->company->removeUser($targetUser);
    }

    public function cancelInvitation(int $invitationId): void
    {
        if (!$this->canManageTeam()) {
            return;
        }

        Invitation::where('id', $invitationId)
            ->where('company_id', $this->company->id)
            ->delete();
    }

    private function getRoleColor(string $roleName): string
    {
        return match ($roleName) {
            'owner' => 'red',
            'admin' => 'amber',
            default => 'zinc',
        };
    }
}; ?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('team.title') }}</flux:heading>
                <flux:text class="text-zinc-500">{{ $this->company->name }} {{ __('team.members') }}</flux:text>
            </div>

            @if ($this->canManageTeam())
                <flux:button variant="primary" wire:click="$set('showInviteModal', true)" class="w-full sm:w-auto">
                    {{ __('team.invite_button') }}
                </flux:button>
            @endif
        </div>

        <!-- Team Members - Mobile Cards -->
        <div class="space-y-3 md:hidden">
            @foreach ($this->users as $user)
                @php
                    setPermissionsTeamId($this->company->id);
                    $userRole = $user->roles->first();
                    $roleName = $userRole?->name ?? 'member';
                    $roleColor = match($roleName) {
                        'owner' => 'red',
                        'admin' => 'amber',
                        default => 'zinc',
                    };
                @endphp
                <div wire:key="user-mobile-{{ $user->id }}"
                    class="p-4 border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <flux:avatar size="sm" :name="$user->name" />
                            <div class="min-w-0">
                                <div class="font-medium truncate">{{ $user->name }}</div>
                                <div class="text-sm truncate text-zinc-500">{{ $user->email }}</div>
                            </div>
                        </div>
                        @if ($this->canManageTeam() && $user->id !== auth()->id() && $roleName !== 'owner')
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    <flux:menu.item wire:click="updateRole({{ $user->id }}, 'admin')">
                                        {{ __('team.make_admin') }}
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="updateRole({{ $user->id }}, 'member')">
                                        {{ __('team.make_member') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item variant="danger" wire:click="removeUser({{ $user->id }})"
                                        wire:confirm="{{ __('team.remove_confirm') }}">
                                        {{ __('team.remove_from_team') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:badge :color="$roleColor">{{ __('team.roles.' . $roleName) }}</flux:badge>
                        <span class="text-sm text-zinc-500">
                            {{ $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('d.m.Y') : '-' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Team Members - Desktop Table -->
        <div class="hidden md:block border rounded-xl border-zinc-200 dark:border-zinc-700 overflow-hidden">
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
                        @php
                            setPermissionsTeamId($this->company->id);
                            $userRole = $user->roles->first();
                            $roleName = $userRole?->name ?? 'member';
                            $roleColor = match($roleName) {
                                'owner' => 'red',
                                'admin' => 'amber',
                                default => 'zinc',
                            };
                        @endphp
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$user->name" />
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $user->name }}</div>
                                        <div class="text-sm truncate text-zinc-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$roleColor">{{ __('team.roles.' . $roleName) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500">
                                {{ $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('d.m.Y') : '-' }}
                            </td>
                            @if ($this->canManageTeam())
                                <td class="px-4 py-3 text-right">
                                    @if ($user->id !== auth()->id() && $roleName !== 'owner')
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                            <flux:menu>
                                                <flux:menu.item wire:click="updateRole({{ $user->id }}, 'admin')">
                                                    {{ __('team.make_admin') }}
                                                </flux:menu.item>
                                                <flux:menu.item wire:click="updateRole({{ $user->id }}, 'member')">
                                                    {{ __('team.make_member') }}
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger" wire:click="removeUser({{ $user->id }})"
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

                <!-- Mobile Cards -->
                <div class="space-y-3 md:hidden">
                    @foreach ($this->pendingInvitations as $invitation)
                        <div wire:key="invitation-mobile-{{ $invitation->id }}"
                            class="p-4 border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium truncate">{{ $invitation->email }}</div>
                                    <div class="text-sm text-zinc-500">{{ $invitation->expires_at->diffForHumans() }}</div>
                                </div>
                                <flux:button variant="ghost" size="sm" icon="x-mark"
                                    wire:click="cancelInvitation({{ $invitation->id }})" />
                            </div>
                            <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                <flux:badge>{{ __('team.roles.' . $invitation->getRoleName()) }}</flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block border rounded-xl border-zinc-200 dark:border-zinc-700 overflow-hidden">
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
                                        <flux:badge>{{ __('team.roles.' . $invitation->getRoleName()) }}</flux:badge>
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