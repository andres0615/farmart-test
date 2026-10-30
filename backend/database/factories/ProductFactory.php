<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'is_special' => false,
        ];
    }

    /**
     * Producto sujeto a control especial.
     */
    public function special(): static
    {
        return $this->state(fn (array $attributes) => ['is_special' => true]);
    }
}
