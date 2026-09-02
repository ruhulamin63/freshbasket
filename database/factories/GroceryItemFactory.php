<?php

namespace Database\Factories;

use App\Models\GroceryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GroceryItem> */
class GroceryItemFactory extends Factory
{
    protected $model = GroceryItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit' => fake()->randomElement(['1 kg', '500 g', '1 litre', '6 pcs']),
            'unit_price_cents' => fake()->numberBetween(1000, 50000),
            'stock_quantity' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
