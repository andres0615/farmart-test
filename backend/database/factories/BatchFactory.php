<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'code' => strtoupper(fake()->unique()->bothify('LOT-####??')),
            'expires_on' => now()->addMonths(6)->toDateString(),
        ];
    }

    /**
     * Lote vencido, útil para pruebas de FEFO y bloqueo por vencimiento.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_on' => now()->subDay()->toDateString(),
        ]);
    }
}
