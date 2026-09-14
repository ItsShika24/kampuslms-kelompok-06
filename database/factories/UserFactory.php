<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake('id_ID')->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => 'mahasiswa',
            'nim_nip'           => fake()->unique()->numerify('20220801####'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** State: akun admin. */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'    => 'admin',
            'nim_nip' => null,
        ]);
    }

    /** State: akun dosen. */
    public function dosen(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'    => 'dosen',
            'nim_nip' => fake()->unique()->numerify('198#########00#'),
        ]);
    }

    /** State: akun mahasiswa. */
    public function mahasiswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'    => 'mahasiswa',
            'nim_nip' => fake()->unique()->numerify('20220801####'),
        ]);
    }
}