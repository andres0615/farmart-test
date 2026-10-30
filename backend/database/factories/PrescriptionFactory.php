<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ];
    }

    /**
     * Receta vencida.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => now()->subDay()->toDateString(),
        ]);
    }
}
