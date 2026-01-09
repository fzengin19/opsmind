<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Models\Calendar;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Calendar>
 */
class CalendarFactory extends Factory
{
    protected $model = Calendar::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement([
                'Genel Takvim',
                'Satış Ekibi',
                'Pazarlama',
                'Toplantı Odası A',
            ]),
            'color' => fake()->hexColor(),
            'type' => CalendarType::Default,
            'visibility' => CalendarVisibility::CompanyWide,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
            'type' => CalendarType::Default,
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn () => [
            'type' => CalendarType::Personal,
            'visibility' => CalendarVisibility::Private,
        ]);
    }
}
