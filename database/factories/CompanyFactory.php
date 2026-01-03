<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'logo' => null,
            'timezone' => 'Europe/Istanbul',
            'settings' => [
                'language' => 'tr',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
            ],
        ];
    }
}
