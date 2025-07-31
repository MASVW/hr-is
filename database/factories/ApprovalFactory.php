<?php

namespace Database\Factories;

use App\Models\Approval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Approval>
 */
class ApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Approval::class;
    public function definition(): array
    {
        return [
            'request_id' => null,
            'status' => $this->faker->randomElement(['approved', 'rejected', 'NA']),
            'is_closed' => $this->faker->boolean(20),
            'approved_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'reason' => $this->faker->optional()->sentence(),
        ];
    }
}
