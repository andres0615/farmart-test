<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }

    /**
     * Reutiliza una receta existente en lugar de crear una nueva.
     */
    public function forPrescription(Prescription $prescription): static
    {
        return $this->state(fn (array $attributes) => [
            'prescription_id' => $prescription->id,
        ]);
    }
}
