<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => fake()->randomElement(ContactType::cases()),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company_name' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'notes' => fake()->optional()->sentence(),
            'tags' => fake()->optional()->randomElements(['VIP', 'Sadık', 'Yeni', 'Önemli'], 2),
            'created_by' => User::factory(),
        ];
    }

    public function customer(): static
    {
        return $this->state(fn () => ['type' => ContactType::Customer]);
    }

    public function lead(): static
    {
        return $this->state(fn () => ['type' => ContactType::Lead]);
    }

    public function vendor(): static
    {
        return $this->state(fn () => ['type' => ContactType::Vendor]);
    }
}
