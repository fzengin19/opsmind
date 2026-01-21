<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PermissionEnum;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * Get all permissions grouped by their category for UI display.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function getGroupedPermissions(): array
    {
        $groups = PermissionEnum::grouped();
        $permissions = Permission::all()
            ->keyBy('name')
            ->map(fn ($perm) => [
                'name' => $perm->name,
                'label' => __('permissions.'.$perm->name),
            ]);

        $grouped = [];

        foreach ($groups as $groupKey => $enums) {
            $groupTitle = match ($groupKey) {
                'company' => __('settings.roles.group_names.company'),
                'user' => __('settings.roles.group_names.user'),
                'contact' => __('settings.roles.group_names.contact'),
                'appointment' => __('settings.roles.group_names.appointment'),
                'task' => __('settings.roles.group_names.task'),
                'role' => __('settings.roles.group_names.role'),
            };

            $grouped[$groupTitle] = collect($enums)
                ->map(fn ($enum) => $permissions->get($enum->value))
                ->filter()
                ->map(fn ($perm) => [
                    'id' => $perm['name'],
                    'label' => $perm['label'],
                ])
                ->values()
                ->all();
        }

        return $grouped;
    }
}
