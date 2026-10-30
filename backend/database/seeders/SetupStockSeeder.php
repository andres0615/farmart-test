<?php

namespace Database\Seeders;

use App\Models\MinStock;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class SetupStockSeeder extends Seeder
{
    public function run(): void
    {
        // Existencias por bodega + lote.
        $stock = [
            [1, 1, 40], [1, 2, 25], [1, 3, 100],
            [1, 4, 18], [1, 5, 60],
            [1, 6, 30], [1, 7, 80],
            [1, 8, 20], [1, 9, 50],
            [1, 10, 70], [1, 11, 120],
            [1, 12, 12], [1, 13, 20],
            [2, 4, 10], [2, 6, 15], [2, 12, 5],
            [3, 3, 60], [3, 5, 40], [3, 7, 50], [3, 11, 80], [3, 13, 10],
        ];

        foreach ($stock as [$warehouseId, $batchId, $quantity]) {
            // Equivale a ON CONFLICT (warehouse_id, batch_id) DO NOTHING.
            $exists = Stock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('batch_id', $batchId)
                ->exists();

            if ($exists) {
                continue;
            }

            Stock::factory()->create([
                'warehouse_id' => $warehouseId,
                'batch_id' => $batchId,
                'quantity' => $quantity,
            ]);
        }

        // Stock mínimo por bodega + producto.
        $minStock = [
            [1, 1, 50], [1, 2, 40], [1, 3, 30], [1, 4, 20], [1, 5, 40], [1, 6, 10],
            [2, 1, 20], [2, 4, 10], [2, 6, 10],
            [3, 3, 25], [3, 5, 30],
        ];

        foreach ($minStock as [$warehouseId, $productId, $minQuantity]) {
            // Equivale a ON CONFLICT (warehouse_id, product_id) DO NOTHING.
            $exists = MinStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                continue;
            }

            MinStock::factory()->create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'min_quantity' => $minQuantity,
            ]);
        }

        // Kardex inicial: una entrada por existencia, sin duplicar en re-ejecuciones.
        $initialMovements = Stock::query()
            ->where('quantity', '>', 0)
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('stock_movements')
                    ->whereColumn('stock_movements.warehouse_id', 'stock.warehouse_id')
                    ->whereColumn('stock_movements.batch_id', 'stock.batch_id')
                    ->where('stock_movements.reason', 'Carga inicial de datos semilla');
            })
            ->get();

        foreach ($initialMovements as $stock) {
            StockMovement::factory()->create([
                'warehouse_id' => $stock->warehouse_id,
                'batch_id' => $stock->batch_id,
                'type' => 'ENTRADA',
                'quantity' => $stock->quantity,
                'reason' => 'Carga inicial de datos semilla',
                'user_id' => 5,
            ]);
        }
    }
}
