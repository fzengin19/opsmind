<?php

declare(strict_types=1);

namespace Database\Factories;

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

    public function withCompany(): static
    {
        return $this->afterCreating(function (User $user) {
            app(\Database\Seeders\RoleSeeder::class)->run();
            app(\App\Actions\Auth\CreateCompanyAction::class)->execute($user, 'Test Company');
        });
    }

    /**
     * Attach user to a company after creation with Spatie role.
     */
    public function forCompany(Company $company, string $roleName = 'member'): static
    {
        return $this->afterCreating(function (User $user) use ($company, $roleName) {
            $company->addUser($user, $roleName);
        });
    }

    /**
     * Create user as company owner.
     */
    public function asOwner(Company $company): static
    {
        return $this->forCompany($company, 'owner');
    }

    /**
     * Create user as company admin.
     */
    public function asAdmin(Company $company): static
    {
        return $this->forCompany($company, 'admin');
    }

    /**
     * Create user as company member.
     */
    public function asMember(Company $company): static
    {
        return $this->forCompany($company, 'member');
    }
}
