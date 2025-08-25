<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Setelah user dibuat, attach 1–3 department acak (jika ada).
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Ambil 1–3 department acak yang sudah ada di DB
            $take = fake()->numberBetween(1, 3);

            $departmentIds = Department::query()
                ->inRandomOrder()
                ->limit($take)
                ->pluck('id');

            if ($departmentIds->isNotEmpty()) {
                // Pastikan relasi di model User ->belongsToMany(Department::class)->withTimestamps()
                $user->departments()->attach($departmentIds->all());
            }
        });
    }

    public function withRandomDepartments(int $min = 1, int $max = 3): static
    {
        return $this->afterCreating(function (User $user) use ($min, $max) {
            $take = fake()->numberBetween($min, $max);
            $ids = Department::inRandomOrder()->limit($take)->pluck('id');
            if ($ids->isNotEmpty()) {
                $user->departments()->attach($ids->all());
            }
        });
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
