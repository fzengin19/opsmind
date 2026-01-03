<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar' => null,
            'phone' => fake()->optional()->phoneNumber(),
            'timezone' => 'Europe/Istanbul',
            'google_id' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Attach user to a company after creation.
     */
    public function forCompany(Company $company, CompanyRole $role = CompanyRole::Member): static
    {
        return $this->afterCreating(function (User $user) use ($company, $role) {
            $company->addUser($user, $role);
        });
    }

    /**
     * Create user as company owner.
     */
    public function asOwner(Company $company): static
    {
        return $this->forCompany($company, CompanyRole::Owner);
    }

    /**
     * Create user as company admin.
     */
    public function asAdmin(Company $company): static
    {
        return $this->forCompany($company, CompanyRole::Admin);
    }

    /**
     * Create user as company manager.
     */
    public function asManager(Company $company): static
    {
        return $this->forCompany($company, CompanyRole::Manager);
    }
}
