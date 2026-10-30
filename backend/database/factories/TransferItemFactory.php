<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TransferItem>
 */
class TransferItemFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transfer_id' => Transfer::factory(),
            'batch_id' => Batch::factory(),
            'quantity_sent' => fake()->numberBetween(1, 20),
            'quantity_received' => null,
        ];
    }

    /**
     * Ítem ya recibido en destino.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_received' => fake()->numberBetween(1, 20),
        ]);
    }
}
