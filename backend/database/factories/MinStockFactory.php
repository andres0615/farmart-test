<?php

namespace Database\Factories;

use App\Models\MinStock;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<MinStock>
 */
class MinStockFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * La tabla min_stock tiene llave primaria compuesta
     * (warehouse_id, product_id) y no tiene columna id.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'min_quantity' => fake()->numberBetween(5, 20),
        ];
    }
}
