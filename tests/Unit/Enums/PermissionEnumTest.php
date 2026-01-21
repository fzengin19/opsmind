<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;

beforeEach(function () {
    //
});

describe('PermissionEnum', function () {
    it('has exactly 23 permissions', function () {
        expect(PermissionEnum::cases())->toHaveCount(23);
    });

    it('all() returns string values', function () {
        $all = PermissionEnum::all();

        expect($all)->toBeArray();
        expect($all)->toHaveCount(23);
        expect($all)->each->toBeString();
    });

    it('all() contains correct permission strings', function () {
        $all = PermissionEnum::all();

        expect($all)->toContain('company.manage');
        expect($all)->toContain('user.view');
        expect($all)->toContain('contact.create');
        expect($all)->toContain('appointment.update');
        expect($all)->toContain('task.delete');
        expect($all)->toContain('role.create');
    });

    it('grouped() organizes by resource', function () {
        $grouped = PermissionEnum::grouped();

        expect($grouped)->toHaveKeys(['company', 'user', 'contact', 'appointment', 'task', 'role']);
        expect($grouped['company'])->toHaveCount(1);
        expect($grouped['user'])->toHaveCount(5);
        expect($grouped['contact'])->toHaveCount(4);
        expect($grouped['appointment'])->toHaveCount(4);
        expect($grouped['task'])->toHaveCount(5);
        expect($grouped['role'])->toHaveCount(4);
    });

    it('memberPermissions() returns correct subset', function () {
        $memberPerms = PermissionEnum::memberPermissions();

        expect($memberPerms)->toHaveCount(7);
        expect($memberPerms)->toContain(PermissionEnum::ContactView);
        expect($memberPerms)->toContain(PermissionEnum::ContactCreate);
        expect($memberPerms)->toContain(PermissionEnum::AppointmentView);
        expect($memberPerms)->toContain(PermissionEnum::AppointmentCreate);
        expect($memberPerms)->toContain(PermissionEnum::TaskView);
        expect($memberPerms)->toContain(PermissionEnum::TaskCreate);
        expect($memberPerms)->toContain(PermissionEnum::TaskUpdate);
    });

    it('label() returns correct translation key', function () {
        expect(PermissionEnum::CompanyManage->label())->toBe('permissions.company.manage');
        expect(PermissionEnum::UserView->label())->toBe('permissions.user.view');
        expect(PermissionEnum::ContactCreate->label())->toBe('permissions.contact.create');
    });

    it('enum values are correct', function () {
        expect(PermissionEnum::CompanyManage->value)->toBe('company.manage');
        expect(PermissionEnum::UserView->value)->toBe('user.view');
        expect(PermissionEnum::ContactCreate->value)->toBe('contact.create');
        expect(PermissionEnum::AppointmentUpdate->value)->toBe('appointment.update');
        expect(PermissionEnum::TaskDelete->value)->toBe('task.delete');
        expect(PermissionEnum::RoleCreate->value)->toBe('role.create');
    });
});
