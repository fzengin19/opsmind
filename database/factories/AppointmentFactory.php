<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+30 days');
        $type = fake()->randomElement(AppointmentType::cases());

        return [
            'company_id' => Company::factory(),
            'title' => fake()->randomElement([
                'Haftalık Toplantı',
                'Sprint Planning',
                'Müşteri Görüşmesi',
                '1-1 Görüşme',
                'Demo Sunumu',
                'Proje Değerlendirme',
                'Brainstorming',
            ]),
            'description' => fake()->optional()->paragraph(),
            'type' => $type,
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+1 hour'),
            'all_day' => false,
            'location' => fake()->optional()->address(),
            'color' => $type->color(),
            'google_calendar_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function meeting(): static
    {
        return $this->state(fn () => [
            'type' => AppointmentType::Meeting,
            'color' => AppointmentType::Meeting->color(),
        ]);
    }

    public function call(): static
    {
        return $this->state(fn () => [
            'type' => AppointmentType::Call,
            'color' => AppointmentType::Call->color(),
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn () => [
            'all_day' => true,
        ]);
    }
}
