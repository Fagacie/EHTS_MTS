<?php

namespace Database\Factories;

use App\Enums\UserStatus;
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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'remember_token' => Str::random(10),
            'status' => UserStatus::Active->value,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function suspended(string $reason = 'Account suspended by administration.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Suspended->value,
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }

    public function deactivated(string $reason = 'Account deactivated by administration.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Deactivated->value,
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }
}
