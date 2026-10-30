<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Transfer>
 */
class TransferFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'origin_id' => Warehouse::factory(),
            'destination_id' => Warehouse::factory(),
            'status' => TransferStatus::from('BORRADOR'),
            'requested_by' => User::factory(),
            'approved_by' => null,
            'notes' => null,
        ];
    }

    /**
     * Asigna un estado concreto al traslado.
     */
    public function withStatus(TransferStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
