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
            ->with('notify', ['variant' => 'success', 'message' => __('settings.roles.save_success')]);
    }
}; ?>

<div class="flex flex-col gap-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $role ? __('settings.roles.edit_title') : __('settings.roles.create_title') }}</flux:heading>
            <flux:subheading>{{ __('settings.roles.form_description') }}</flux:subheading>
        </div>
    </div>

    <!-- Form Card -->
    <form wire:submit="save">
        <div class="p-4 sm:p-6 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm space-y-6">

            <flux:field>
                <flux:label>{{ __('settings.roles.field_name') }}</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:separator />

            <div class="space-y-4">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                    {{ __('settings.roles.permissions_title') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($groupedPermissions as $group => $permissions)
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <h4 class="font-medium text-zinc-700 dark:text-zinc-300 mb-3">{{ $group }}</h4>
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

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:button href="{{ route('settings.roles.index') }}" wire:navigate>
                    {{ __('settings.roles.cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">{{ __('settings.roles.save') }}</flux:button>
            </div>
        </div>
    </form>
</div>