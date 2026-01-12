<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount()
    {
        setPermissionsTeamId(auth()->user()->currentCompany()->id);
        Gate::authorize('role.view');
    }

    public function with(): array
    {
        $company = auth()->user()->currentCompany();
        setPermissionsTeamId($company->id);

        return [
            'roles' => Role::where('company_id', $company->id)
                ->withCount('users')
                ->get(),
        ];
    }

    public function delete(Role $role)
    {
        Gate::authorize('role.delete');

        if (strtolower($role->name) === 'owner') {
            abort(403, __('settings.roles.error_owner_delete'));
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('notify', variant: 'danger', message: __('settings.roles.error_has_members'));
            return;
        }

        // Ensure role belongs to this company
        if ($role->company_id !== auth()->user()->currentCompany()->id) {
            abort(403);
        }

        $role->delete();

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->dispatch('notify', variant: 'success', message: __('settings.roles.delete_success'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('settings.roles.title') }}</flux:heading>
            <flux:subheading>{{ __('settings.roles.description') }}</flux:subheading>
        </div>
        @can('role.create')
            <flux:button variant="primary" href="{{ route('settings.roles.create') }}" wire:navigate>
                {{ __('settings.roles.button_create') }}
            </flux:button>
        @endcan
    </div>

    <!-- Table Card -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            {{ __('settings.roles.table_name') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            {{ __('settings.roles.table_members') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            {{ __('settings.roles.table_actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($roles as $role)
                        <tr wire:key="{{ $role->id }}">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $role->name }}
                                @if(strtolower($role->name) === 'owner')
                                    <flux:badge size="sm" color="zinc" class="ml-2">{{ __('settings.roles.system_role') }}
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $role->users_count }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    @if(strtolower($role->name) !== 'owner')
                                        @can('role.update')
                                            <flux:button size="sm" icon="pencil-square"
                                                href="{{ route('settings.roles.edit', $role) }}" wire:navigate />
                                        @endcan

                                        @can('role.delete')
                                            <flux:button size="sm" variant="danger" icon="trash"
                                                wire:click="delete({{ $role->id }})"
                                                wire:confirm="{{ __('settings.roles.delete_confirm_title') }}" />
                                        @endcan
                                    @else
                                        <span
                                            class="text-zinc-400 text-sm italic">{{ __('settings.roles.protected_role') }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>