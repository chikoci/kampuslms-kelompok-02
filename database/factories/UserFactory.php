<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
        $fakerId = fake('id_ID');

        return [
            'name' => $fakerId->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'mahasiswa',
            'nim_nip' => '220' . fake()->unique()->numerify('#######'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * State untuk role admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'nim_nip' => null,
        ]);
    }

    /**
     * State untuk role dosen.
     */
    public function lecturer(): static
    {
        return $this->dosen();
    }

    public function dosen(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Dr. ' . fake('id_ID')->name() . ', M.Kom.',
            'role' => 'dosen',
            'nim_nip' => '198' . fake()->unique()->numerify('###############'),
        ]);
    }

    /**
     * State untuk role mahasiswa.
     */
    public function student(): static
    {
        return $this->mahasiswa();
    }

    public function mahasiswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mahasiswa',
            'nim_nip' => '220' . fake()->unique()->numerify('#######'),
        ]);
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
}
