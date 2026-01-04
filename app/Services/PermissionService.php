<?php

declare(strict_types=1);

namespace App\Services;

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
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            // Permission naming convention: group.action (e.g., user.create)
            $parts = explode('.', $permission->name);
            $groupKey = $parts[0] ?? 'other';
            
            // Map group key to readable title
            $groupTitle = match ($groupKey) {
                'company' => __('Roles & Permissions'), // Reusing valid translation or generic? Let's use custom titles.
                'user' => __('Users'),
                'contact' => __('Contacts'),
                'appointment' => __('Calendar'),
                'task' => __('Tasks'),
                'role' => __('Roles'),
                default => ucfirst($groupKey),
            };

            // Setup group if not exists
            if (! isset($grouped[$groupTitle])) {
                $grouped[$groupTitle] = [];
            }

            $grouped[$groupTitle][] = [
                'id' => $permission->name,
                'label' => __('permissions.' . $permission->name),
            ];
        }

        // Sort groups by key just to be consistent (optional)
        ksort($grouped);

        return $grouped;
    }
}
