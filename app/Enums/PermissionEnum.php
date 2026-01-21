<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    // Company Management
    case CompanyManage = 'company.manage';

    // User Management
    case UserView = 'user.view';
    case UserCreate = 'user.create';
    case UserUpdate = 'user.update';
    case UserDelete = 'user.delete';
    case UserInvite = 'user.invite';

    // Contact Management
    case ContactView = 'contact.view';
    case ContactCreate = 'contact.create';
    case ContactUpdate = 'contact.update';
    case ContactDelete = 'contact.delete';

    // Appointment/Calendar Management
    case AppointmentView = 'appointment.view';
    case AppointmentCreate = 'appointment.create';
    case AppointmentUpdate = 'appointment.update';
    case AppointmentDelete = 'appointment.delete';

    // Task Management
    case TaskView = 'task.view';
    case TaskCreate = 'task.create';
    case TaskUpdate = 'task.update';
    case TaskDelete = 'task.delete';
    case TaskAssign = 'task.assign';

    // Role Management
    case RoleView = 'role.view';
    case RoleCreate = 'role.create';
    case RoleUpdate = 'role.update';
    case RoleDelete = 'role.delete';

    /**
     * Get all permissions as an array of strings.
     * Compatible with Spatie's givePermissionTo().
     */
    public static function all(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Get permissions grouped by resource.
     */
    public static function grouped(): array
    {
        return [
            'company' => [self::CompanyManage],
            'user' => [
                self::UserView,
                self::UserCreate,
                self::UserUpdate,
                self::UserDelete,
                self::UserInvite,
            ],
            'contact' => [
                self::ContactView,
                self::ContactCreate,
                self::ContactUpdate,
                self::ContactDelete,
            ],
            'appointment' => [
                self::AppointmentView,
                self::AppointmentCreate,
                self::AppointmentUpdate,
                self::AppointmentDelete,
            ],
            'task' => [
                self::TaskView,
                self::TaskCreate,
                self::TaskUpdate,
                self::TaskDelete,
                self::TaskAssign,
            ],
            'role' => [
                self::RoleView,
                self::RoleCreate,
                self::RoleUpdate,
                self::RoleDelete,
            ],
        ];
    }

    /**
     * Get basic permissions for member role.
     */
    public static function memberPermissions(): array
    {
        return [
            self::ContactView,
            self::ContactCreate,
            self::AppointmentView,
            self::AppointmentCreate,
            self::TaskView,
            self::TaskCreate,
            self::TaskUpdate,
        ];
    }

    /**
     * Get the label for translation key.
     */
    public function label(): string
    {
        return "permissions.{$this->value}";
    }
}
