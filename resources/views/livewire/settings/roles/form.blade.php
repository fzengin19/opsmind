<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Role $role = null;

    public string $name = '';
    public array $selectedPermissions = [];
    public array $groupedPermissions = [];

    public function mount(PermissionService $service, ?Role $role = null)
    {
        // Calculate grouped permissions once
        $this->groupedPermissions = $service->getGroupedPermissions();

        if ($role && $role->exists) {
            Gate::authorize('role.update');

            // Security Check: Owner role is protected
            if (strtolower($role->name) === 'owner') {
                return redirect()->route('settings.roles.index');
            }

            // Security Check: Role must belong to current company
            $companyId = auth()->user()->currentCompany()->id;
            if ($role->company_id !== $companyId) {
                abort(403);
            }

            $this->role = $role;
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        } else {
            Gate::authorize('role.create');
        }
    }

    public function save()
    {
        $companyId = auth()->user()->currentCompany()->id;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($this->role?->id)
            ],
            'selectedPermissions' => ['array'],
        ];

        $this->validate($rules);

        setPermissionsTeamId($companyId);

        if ($this->role) {
            // Update
            $this->role->update(['name' => $this->name]);
            $role = $this->role;
        } else {
            // Create
            $role = Role::create([
                'name' => $this->name,
                'guard_name' => 'web',
                'company_id' => $companyId
            ]);
        }

        // Sync permissions
        $validPermissions = Permission::whereIn('name', $this->selectedPermissions)->pluck('name')->toArray();
        $role->syncPermissions($validPermissions);

        // Clear cache explicitly
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('settings.roles.index')
            ->with('notify', ['variant' => 'success', 'message' => 'Role saved successfully.']);
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $role ? __('Edit Role') : __('Create Role') }}</flux:heading>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form wire:submit="save">
                <div
                    class="bg-white dark:bg-zinc-800 shadow-sm sm:rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-6">

                    <flux:field>
                        <flux:label>{{ __('Role Name') }}</flux:label>
                        <flux:input wire:model="name" type="text" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:separator />

                    <div class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Permissions') }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($groupedPermissions as $group => $permissions)
                                <div
                                    class="bg-gray-50 dark:bg-zinc-900 p-4 rounded-lg border border-gray-200 dark:border-zinc-700">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $group }}</h4>
                                    <div class="space-y-2">
                                        @foreach($permissions as $permission)
                                            <flux:checkbox wire:model="selectedPermissions" value="{{ $permission['id'] }}"
                                                label="{{ $permission['label'] }}" />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <flux:button href="{{ route('settings.roles.index') }}" wire:navigate>{{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Save Role') }}</flux:button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>