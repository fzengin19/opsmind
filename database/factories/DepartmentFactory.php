<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement([
                'Yazılım Geliştirme',
                'Satış',
                'Pazarlama',
                'İnsan Kaynakları',
                'Finans',
                'Müşteri Hizmetleri',
                'Operasyon',
            ]),
            'parent_id' => null,
        ];
    }

    public function withParent(Department $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'company_id' => $parent->company_id,
        ]);
    }
}
