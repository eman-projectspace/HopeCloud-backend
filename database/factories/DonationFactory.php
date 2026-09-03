<?php

namespace Database\Factories;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => null,
            'category' => fake()->randomElement(['Food', 'Clothing', 'Books']),
            'condition' => null,
            'location' => null,
            'image' => null,
            'quantity' => fake()->numberBetween(1, 10),
            'preferred_date' => null,
            'notes' => null,
            'status' => 'submitted',
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    public function inCategory(string $category): static
    {
        return $this->state(fn (): array => [
            'category' => $category,
        ]);
    }

    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (): array => [
            'quantity' => $quantity,
        ]);
    }
}
