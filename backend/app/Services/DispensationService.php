<?php

namespace App\Services;

use App\Exceptions\PrescriptionExpiredException;
use App\Models\DispensationItem;
use App\Models\Prescription;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;

class DispensationService
{
    /**
     * Simula la asignación FEFO sin efectos secundarios.
     *
     * @return array<string, mixed>
     */
    public function preview(Prescription $prescription, Warehouse $warehouse): array
    {
        $today = now()->toDateString();

        // Verificar si la prescripción está vigente
        if ($prescription->valid_until->toDateString() < $today) {
            throw new PrescriptionExpiredException();
        }

        $items = $prescription->items;
        $itemIds = $items->pluck('id')->all();
        $productIds = $items->pluck('product_id')->unique()->values()->all();

        // Total dispensado por ítem de prescripción (todas las bodegas).
        $dispensed = DispensationItem::query()
            ->whereIn('prescription_item_id', $itemIds)
            ->selectRaw('prescription_item_id, SUM(quantity) as total')
            ->groupBy('prescription_item_id')
            ->pluck('total', 'prescription_item_id');

        // Stock disponible en la bodega, ordenado por FEFO.
        $stocks = Stock::query()
            ->join('batches', 'batches.id', '=', 'stock.batch_id')
            ->where('stock.warehouse_id', $warehouse->id)
            ->where('stock.quantity', '>', 0)
            ->whereIn('batches.product_id', $productIds)
            ->orderBy('batches.expires_on')
            ->orderBy('batches.id')
            ->get([
                'stock.batch_id',
                'stock.quantity as available',
                'batches.code as batch_code',
                'batches.expires_on',
                'batches.product_id',
            ]);

        $validStock = [];
        $expiredCounts = [];

        foreach ($stocks as $row) {
            $productId = (int) $row->product_id;
            $expiresOn = Carbon::parse($row->expires_on)->toDateString();

            if ($expiresOn < $today) {
                $expiredCounts[$productId] = ($expiredCounts[$productId] ?? 0) + 1;
                continue;
            }

            $validStock[$productId][] = [
                'batch_id' => (int) $row->batch_id,
                'batch_code' => $row->batch_code,
                'expires_on' => $expiresOn,
                'available' => (int) $row->available,
            ];
        }

        $previewItems = [];
        $requiresAuthorization = false;

        foreach ($items as $item) {
            $prescribed = (int) $item->quantity;
            $alreadyDispensed = (int) ($dispensed[$item->id] ?? 0);
            $remaining = max(0, $prescribed - $alreadyDispensed);

            $allocations = [];
            $availableTotal = 0;
            $pending = $remaining;

            foreach ($validStock[$item->product_id] ?? [] as $batch) {
                $availableTotal += $batch['available'];

                if ($pending <= 0) {
                    continue;
                }

                $take = min($batch['available'], $pending);

                $allocations[] = [
                    'batch_id' => $batch['batch_id'],
                    'batch_code' => $batch['batch_code'],
                    'expires_on' => $batch['expires_on'],
                    'available' => $batch['available'],
                    'take' => $take,
                ];

                $pending -= $take;
            }

            $warnings = [];

            if ($remaining > $availableTotal) {
                $missing = $remaining - $availableTotal;
                $warnings[] = "Stock insuficiente: faltan {$missing} unidades.";
            }

            $expired = (int) ($expiredCounts[$item->product_id] ?? 0);
            if ($expired > 0) {
                $warnings[] = "Hay {$expired} lote(s) vencido(s) ignorado(s).";
            }

            if ($remaining > 0 && $item->product->is_special) {
                $requiresAuthorization = true;
            }

            $previewItems[] = [
                'prescription_item_id' => (int) $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'is_special' => (bool) $item->product->is_special,
                ],
                'prescribed' => $prescribed,
                'already_dispensed' => $alreadyDispensed,
                'remaining' => $remaining,
                'allocations' => $allocations,
                'warnings' => $warnings,
            ];
        }

        return [
            'prescription' => [
                'id' => $prescription->id,
                'patient_id' => $prescription->patient_id,
                'doctor_id' => $prescription->doctor_id,
                'valid_until' => $prescription->valid_until->toDateString(),
                'created_at' => $prescription->created_at?->toIso8601String(),
                'patient' => $prescription->patient ? [
                    'id' => $prescription->patient->id,
                    'document' => $prescription->patient->document,
                    'full_name' => $prescription->patient->full_name,
                ] : null,
            ],
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ],
            'items' => $previewItems,
            'requires_authorization' => $requiresAuthorization,
        ];
    }
}
