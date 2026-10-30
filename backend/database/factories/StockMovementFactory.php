<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * Se usa el primer caso del enum para no inventar valores del dominio;
     * usa ofType() cuando la prueba necesite un tipo concreto.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'batch_id' => Batch::factory(),
            'type' => StockMovementType::cases()[0],
            'quantity' => fake()->numberBetween(1, 50),
            'reason' => 'Movimiento generado por factory',
            'dispensation_id' => null,
            'transfer_id' => null,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Asigna un tipo de movimiento concreto.
     */
    public function ofType(StockMovementType $type, ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'reason' => $reason,
        ]);
    }
}
