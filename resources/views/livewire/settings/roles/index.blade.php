<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount()
    {
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
            abort(403, 'Owner role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('notify', variant: 'danger', message: 'Cannot delete role with active members.');
            return;
        }

        // Ensure role belongs to this company
        if ($role->company_id !== auth()->user()->currentCompany()->id) {
            abort(403);
        }

        $role->delete();

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->dispatch('notify', variant: 'success', message: 'Role deleted successfully.');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Roles & Permissions') }}</flux:heading>

        @can('role.create')
            <flux:button variant="primary" href="{{ route('settings.roles.create') }}" wire:navigate>
                {{ __('Create Role') }}
            </flux:button>
        @endcan
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div
                class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    {{ __('Role Name') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    {{ __('Members') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($roles as $role)
                                <tr wire:key="{{ $role->id }}">
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $role->name }}
                                        @if(strtolower($role->name) === 'owner')
                                            <flux:badge size="sm" color="zinc" class="ml-2">{{ __('System') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $role->users_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            @if(strtolower($role->name) !== 'owner')
                                                @can('role.update')
                                                    <flux:button size="sm" icon="pencil-square"
                                                        href="{{ route('settings.roles.edit', $role) }}" wire:navigate />
                                                @endcan

                                                @can('role.delete')
                                                    <flux:button size="sm" variant="danger" icon="trash"
                                                        wire:click="delete({{ $role->id }})"
                                                        wire:confirm="{{ __('Are you sure you want to delete this role?') }}" />
                                                @endcan
                                            @else
                                                <span class="text-zinc-400 text-sm italic">{{ __('Protected') }}</span>
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
    </div>
</div>