<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Auth\CreateCompanyAction;
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
        // First run RoleSeeder to create permissions
        $this->call(RoleSeeder::class);

        // Create admin user first
        $admin = User::factory()->create([
            'email' => 'admin@opsmind.test',
            'name' => 'Admin User',
        ]);

        // Use CreateCompanyAction which creates company + default roles + owner
        $action = new CreateCompanyAction;
        $company = $action->execute($admin, 'OpsMind Demo');

        // Create departments
        $engineering = Department::create([
            'company_id' => $company->id,
            'name' => 'Yazılım Geliştirme',
        ]);

        $sales = Department::create([
            'company_id' => $company->id,
            'name' => 'Satış',
        ]);

        // Create manager user
        $manager = User::factory()->create([
            'email' => 'manager@opsmind.test',
            'name' => 'Manager User',
        ]);
        $company->addUser($manager, 'admin', $sales->id, 'Satış Müdürü');

        // Create member user
        $member = User::factory()->create([
            'email' => 'member@opsmind.test',
            'name' => 'Team Member',
        ]);
        $company->addUser($member, 'member', $engineering->id, 'Developer');

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
