<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->randomElement([
                'Müşteri raporunu hazırla',
                'API entegrasyonunu tamamla',
                'UI tasarımını gözden geçir',
                'Test senaryoları yaz',
                'Dokümantasyonu güncelle',
                'Bug fix - login ekranı',
                'Performance optimizasyonu',
            ]),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+14 days'),
            'estimated_hours' => fake()->optional()->randomFloat(1, 0.5, 8),
            'assignee_id' => null,
            'contact_id' => null,
            'appointment_id' => null,
            'position' => fake()->numberBetween(0, 100),
            'checklist' => null,
            'created_by' => User::factory(),
        ];
    }

    public function backlog(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Backlog]);
    }

    public function todo(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Todo]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::InProgress]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Done]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => ['priority' => TaskPriority::Urgent]);
    }

    public function withChecklist(): static
    {
        return $this->state(fn () => [
            'checklist' => [
                ['text' => 'İlk adım', 'done' => true],
                ['text' => 'İkinci adım', 'done' => false],
                ['text' => 'Üçüncü adım', 'done' => false],
            ],
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => [
            'assignee_id' => $user->id,
            'company_id' => $user->company_id,
        ]);
    }
}
