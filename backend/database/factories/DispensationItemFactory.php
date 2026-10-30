<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Dispensation;
use App\Models\DispensationItem;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<DispensationItem>
 */
class DispensationItemFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dispensation_id' => Dispensation::factory(),
            'prescription_item_id' => PrescriptionItem::factory(),
            'batch_id' => Batch::factory(),
            'quantity' => 1,
        ];
    }

    /**
     * Variante coherente: el lote pertenece al mismo producto del ítem de receta.
     */
    public function consistent(): static
    {
        return $this->state(function () {
            $item = PrescriptionItem::factory()->create();

            return [
                'prescription_item_id' => $item->id,
                'batch_id' => Batch::factory()->create(['product_id' => $item->product_id])->id,
            ];
        });
    }
}
