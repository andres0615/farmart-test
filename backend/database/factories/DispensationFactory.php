<?php

namespace Database\Factories;

use App\Models\Dispensation;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Dispensation>
 */
class DispensationFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'prescription_id' => Prescription::factory(),
            'warehouse_id' => Warehouse::factory(),
            'dispensed_by' => User::factory(),
            'authorized_by' => null,
        ];
    }

    /**
     * Dispensación de producto especial: requiere un usuario autorizador.
     */
    public function authorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'authorized_by' => User::factory(),
        ]);
    }
}
