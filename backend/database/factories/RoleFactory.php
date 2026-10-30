<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
        ];
    }

    /**
     * Crea un rol con un nombre concreto.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => ['name' => $name]);
    }
}
