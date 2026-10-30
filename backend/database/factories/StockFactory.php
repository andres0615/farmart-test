<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * La tabla stock tiene llave primaria compuesta (warehouse_id, batch_id)
     * y no tiene columna id.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'batch_id' => Batch::factory(),
            'quantity' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Existencia en cero.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => ['quantity' => 0]);
    }
}
