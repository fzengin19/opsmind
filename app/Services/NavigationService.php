<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    public function getSidebarMenu(): array
    {
        return [
            'company_name' => auth()->user()->currentCompany()?->name,
            'apps' => [
                [
                    'label' => __('navigation.dashboard'),
                    'route' => 'dashboard',
                    'icon' => 'home',
                    'active' => request()->routeIs('dashboard'),
                ],
            ],
            'management' => $this->getManagementLinks(),
        ];
    }

    private function getManagementLinks(): array
    {
        $links = [];

        // Team Management
        if (auth()->user()->can('viewAny', User::class)) {
            $links[] = [
                'label' => __('team.title'),
                'route' => 'team.index',
                'icon' => 'users',
                'active' => request()->routeIs('team.*'),
            ];
        }

        // Role Management
        if (auth()->user()->can('role.view')) {
            $links[] = [
                'label' => __('navigation.roles'),
                'route' => 'settings.roles.index',
                'icon' => 'shield-check',
                'active' => request()->routeIs('settings.roles.*'),
            ];
        }

        return $links;
    }
}
