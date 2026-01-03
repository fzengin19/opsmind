<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // First run RoleSeeder
        $this->call(RoleSeeder::class);

        // Create demo company
        $company = Company::create([
            'name' => 'OpsMind Demo',
            'slug' => 'opsmind-demo',
            'timezone' => 'Europe/Istanbul',
            'settings' => [
                'language' => 'tr',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
            ],
        ]);

        // Create departments
        $engineering = Department::create([
            'company_id' => $company->id,
            'name' => 'Yazılım Geliştirme',
        ]);

        $sales = Department::create([
            'company_id' => $company->id,
            'name' => 'Satış',
        ]);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@opsmind.test'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'department_id' => $engineering->id,
                'job_title' => 'CTO',
                'timezone' => 'Europe/Istanbul',
            ]
        );
        $admin->assignRole('admin');

        // Create manager user
        $manager = User::firstOrCreate(
            ['email' => 'manager@opsmind.test'],
            [
                'name' => 'Manager User',
                'password' => 'password',
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'department_id' => $sales->id,
                'job_title' => 'Satış Müdürü',
                'timezone' => 'Europe/Istanbul',
            ]
        );
        $manager->assignRole('manager');

        // Create member user
        $member = User::firstOrCreate(
            ['email' => 'member@opsmind.test'],
            [
                'name' => 'Team Member',
                'password' => 'password',
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'department_id' => $engineering->id,
                'job_title' => 'Developer',
                'timezone' => 'Europe/Istanbul',
            ]
        );
        $member->assignRole('member');

        // Create 20 contacts
        Contact::factory(20)->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
        ]);

        // Create 15 appointments
        Appointment::factory(15)->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
        ]);

        // Create 50 tasks
        Task::factory(50)->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'assignee_id' => fake()->randomElement([$admin->id, $manager->id, $member->id, null]),
        ]);
    }
}
