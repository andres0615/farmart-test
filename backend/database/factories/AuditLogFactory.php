<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => 'created',
            'entity' => 'Product',
            'entity_id' => null,
        ];
    }

    /**
     * Registro de auditoría asociado a una entidad concreta.
     */
    public function forEntity(string $entity, ?int $entityId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'entity' => $entity,
            'entity_id' => $entityId,
        ]);
    }
}
