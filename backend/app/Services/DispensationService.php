<?php

namespace App\Services;

use App\Exceptions\DispensationException;
use App\Exceptions\PrescriptionExpiredException;
use App\Models\Dispensation;
use App\Models\DispensationItem;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DispensationService
{
    /**
     * Simula la asignación FEFO sin efectos secundarios.
     *
     * @return array<string, mixed>
     */
    public function preview(Prescription $prescription, Warehouse $warehouse): array
    {
        // Vista previa: NO guarda nada. Solo calcula qué lotes se usarían en cada
        // medicamento, para que el usuario revise antes de confirmar la dispensación.
        // Fecha de hoy como texto "AAAA-MM-DD" (sin horas), para poder compararla
        // con `valid_until`, que también es una fecha sin hora.
        $today = now()->toDateString();

        // Verificar si la prescripción está vigente
        if ($prescription->valid_until->toDateString() < $today) {
            throw new PrescriptionExpiredException();
        }

        // Se extraen dos listas de la receta:
        // - `$itemIds`: el id de cada línea de la receta.
        // - `$productIds`: los productos sin repetir, para buscar su stock una sola vez.
        // Ojo: este método asume que quien llama ya cargó `items.product`
        // (eager loading); si no, cada ítem haría una consulta extra.
        $items = $prescription->items;
        $itemIds = $items->pluck('id')->all();
        $productIds = $items->pluck('product_id')->unique()->values()->all();

        // Total dispensado por ítem de prescripción (todas las bodegas).
        // `$dispensed` es un diccionario "id de línea => unidades ya dispensadas".
        $dispensed = $this->remainingByItem($itemIds);

        // Stock disponible en la bodega, ordenado por FEFO.
        // `allocateFefo()` devuelve dos listas y esta sintaxis guarda cada una en su
        // variable: `$validStock` (lotes que sí se pueden usar) y `$expiredCounts`
        // (cuántos lotes vencidos se encontraron por producto).
        ['valid' => $validStock, 'expired' => $expiredCounts] = $this->allocateFefo($warehouse->id, $productIds);

        // Acumuladores del resultado: el detalle por ítem y una bandera que se
        // encenderá si algún medicamento especial requiere autorización.
        $previewItems = [];
        $requiresAuthorization = false;

        // Se procesa cada línea de la receta para armar su parte del preview.
        foreach ($items as $item) {
            // Tres cantidades por ítem: lo recetado, lo ya dispensado y lo que falta.
            // `max(0, ...)` evita que lo pendiente quede en negativo por datos viejos.
            $prescribed = (int) $item->quantity;
            $alreadyDispensed = (int) ($dispensed[$item->id] ?? 0);
            $remaining = max(0, $prescribed - $alreadyDispensed);

            // Variables de trabajo del ítem:
            // - `$allocations`: lotes elegidos para este ítem.
            // - `$availableTotal`: suma del stock usable del producto.
            // - `$pending`: lo que aún falta asignar al recorrer los lotes.
            $allocations = [];
            $availableTotal = 0;
            $pending = $remaining;

            // Se recorren los lotes del producto ya ordenados por FEFO (vence primero)
            // y se van asignando unidades hasta cubrir lo pendiente.
            foreach ($validStock[$item->product_id] ?? [] as $batch) {
                // Se suma el stock usable de todos los lotes, aunque no se vaya a usar todo.
                $availableTotal += $batch['available'];

                if ($pending <= 0) {
                    // Ya no hace falta asignar más, pero se sigue sumando stock para el aviso.
                    continue;
                }

                // Se toma lo máximo posible sin pasarse de lo que falta ni de lo disponible.
                $take = min($batch['available'], $pending);

                // Se registra esta asignación parcial: de qué lote y cuántas unidades.
                $allocations[] = [
                    'batch_id' => $batch['batch_id'],
                    'batch_code' => $batch['batch_code'],
                    'expires_on' => $batch['expires_on'],
                    'available' => $batch['available'],
                    'take' => $take,
                ];

                // Se descuenta lo asignado; cuando llega a 0, ya no se toma de más lotes.
                $pending -= $take;
            }

            // Avisos que se mostrarán al usuario sobre este ítem (sin bloquear el preview).
            $warnings = [];

            // Si el stock no alcanza para lo pendiente, se indica cuántas unidades faltan.
            if ($remaining > $availableTotal) {
                $missing = $remaining - $availableTotal;
                $warnings[] = "Stock insuficiente: faltan {$missing} unidades.";
            }

            // Los lotes vencidos no se usan, pero se avisa cuántos se ignoraron.
            $expired = (int) ($expiredCounts[$item->product_id] ?? 0);
            if ($expired > 0) {
                $warnings[] = "Hay {$expired} lote(s) vencido(s) ignorado(s).";
            }

            // Un medicamento especial pendiente obligará a pedir autorización al
            // confirmar la dispensación (basta con que haya uno).
            if ($remaining > 0 && $item->product->is_special) {
                $requiresAuthorization = true;
            }

            // Se agrega el ítem ya procesado a la lista del resultado.
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

        // Respuesta final: datos de la receta, la bodega, el detalle por ítem y la
        // bandera que indica si hace falta autorización.
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

    /**
     * Registra una dispensación aplicando FEFO y descontando el stock.
     *
     * Todo ocurre dentro de una transacción: si un paso falla, no se guarda
     * nada (ni la dispensación ni el descuento de stock). Los pasos son:
     * 1. Si la clave de idempotencia ya existe, devolver esa dispensación.
     * 2. Bloquear la receta y comprobar que sigue vigente.
     * 3. Calcular de qué lotes sale cada medicamento (FEFO).
     * 4. Exigir autorización si hay medicamentos especiales.
     * 5. Crear la dispensación y descontar el stock.
     *
     * @param  list<array{prescription_item_id: int|string, quantity: int|string}>  $items
     */
    public function create(
        Prescription $prescription,
        Warehouse $warehouse,
        User $user,
        array $items,
        ?int $authorizedBy,
        string $idempotencyKey,
    ): Dispensation {
        // `DB::transaction` abre una transacción: los cambios se confirman
        // juntos al final o se deshacen completos si algo lanza una excepción.
        return DB::transaction(function () use ($prescription, $warehouse, $user, $items, $authorizedBy, $idempotencyKey) {
            // Idempotencia: si el cliente reenvía la misma `Idempotency-Key`
            // (por ejemplo tras un timeout), devolvemos la dispensación que ya
            // existe en lugar de dispensar dos veces.
            if ($existing = Dispensation::where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            // Lock de la prescripción para serializar confirmaciones simultáneas.
            // `lockForUpdate()` bloquea la fila hasta el final de la transacción
            // y `firstOrFail()` lanza 404 si la receta ya no existe.
            $prescription = Prescription::whereKey($prescription->id)->lockForUpdate()->firstOrFail();

            // Una receta vencida no se puede dispensar (error de negocio 422).
            if ($prescription->valid_until->toDateString() < now()->toDateString()) {
                throw new PrescriptionExpiredException();
            }

            // Calcula las extracciones por lote (FEFO) sin escribir en la base.
            $rows = $this->buildAllocations($prescription, $warehouse, $items);

            // Si alguna extracción es de un medicamento especial y no se indicó
            // quién autoriza, se corta con el código REQUIRES_AUTHORIZATION.
            if ($rows->contains('is_special', true) && $authorizedBy === null) {
                throw DispensationException::requiresAuthorization(
                    'Este medicamento requiere autorización de un regente de farmacia.'
                );
            }

            // `firstOrCreate` intenta crear la dispensación; si otra petición
            // ganó la carrera con la misma clave, devuelve la existente sin error.
            $dispensation = Dispensation::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'prescription_id' => $prescription->id,
                    'warehouse_id' => $warehouse->id,
                    'dispensed_by' => $user->id,
                    'authorized_by' => $authorizedBy,
                ]
            );

            // Solo si la dispensación es nueva se descuenta el stock: en un
            // reintento idempotente no se vuelve a descontar.
            if ($dispensation->wasRecentlyCreated) {
                $this->persistAllocations($dispensation, $warehouse, $rows);
            }

            return $dispensation;
        });
    }

    /**
     * Calcula de qué lotes sacar cada medicamento, sin guardar nada todavía.
     *
     * - Entrada: la receta, la bodega y los ítems que el usuario quiere dispensar.
     * - Salida: una lista de "toma X unidades del lote Y para el ítem Z".
     *
     * FEFO significa "lo que vence primero, sale primero": se usan antes los
     * lotes con la fecha de vencimiento más cercana.
     *
     * @param  list<array{prescription_item_id: int|string, quantity: int|string}>  $items
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildAllocations(Prescription $prescription, Warehouse $warehouse, array $items)
    {
        // Líneas de la receta indexadas por id (diccionario "id => ítem").
        $prescriptionItems = $this->loadPrescriptionItems($prescription);

        // Cuánto se dispensó antes de cada ítem: diccionario "id => cantidad".
        $alreadyDispensed = $this->remainingByItem($prescriptionItems->keys()->all());

        // Stock por producto en esta bodega, ordenado por fecha de vencimiento
        // (FEFO) y sin los lotes ya vencidos. `['valid' => ...]` toma solo la
        // parte útil del arreglo que devuelve `allocateFefo()`.
        $stockByProduct = $this->allocateFefo(
            $warehouse->id,
            $prescriptionItems->pluck('product_id')->unique()->values()->all()
        )['valid'];

        // Cada ítem pedido se reparte entre lotes y los resultados se aplanan
        // en una sola lista de extracciones.
        $rows = collect($items)->flatMap(
            fn (array $item) => $this->allocateItem($item, $prescriptionItems, $alreadyDispensed, $stockByProduct)
        );

        if ($rows->isEmpty()) {
            throw DispensationException::prescriptionExceeded('No hay cantidades para dispensar.');
        }

        return $rows;
    }

    /**
     * Carga las líneas de la receta indexadas por id.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\PrescriptionItem>
     */
    private function loadPrescriptionItems(Prescription $prescription)
    {
        // `keyBy('id')` convierte la lista en un diccionario "id => ítem" para
        // poder buscar cada línea de la receta en una operación.
        return $prescription->items()->with('product')->get()->keyBy('id');
    }

    /**
     * Reparte la cantidad pedida de un solo ítem entre los lotes disponibles.
     *
     * - Entrada: un ítem que el usuario pidió (`$requested`), las líneas de la
     *   receta, lo ya dispensado y el stock agrupado por producto.
     * - Salida: la lista de extracciones "lote X, cantidad Y" para ese ítem.
     *
     * Si el ítem no es de la receta o se pide más de lo pendiente, se corta
     * con un error de negocio; en ese caso no se guarda nada.
     *
     * @param  array{prescription_item_id: int|string, quantity: int|string}  $requested
     * @param  \Illuminate\Support\Collection<int, \App\Models\PrescriptionItem>  $prescriptionItems
     * @param  array<int|string, mixed>  $alreadyDispensed
     * @param  array<int, list<array<string, mixed>>>  $stockByProduct
     * @return list<array<string, mixed>>
     */
    private function allocateItem(
        array $requested,
        $prescriptionItems,
        array $alreadyDispensed,
        array $stockByProduct,
    ): array {
        // Busca la línea de la receta a la que se refiere el ítem pedido.
        // `(int)` convierte el id porque desde HTTP puede llegar como texto.
        $prescriptionItem = $prescriptionItems->get((int) $requested['prescription_item_id']);

        // Si no aparece, el ítem no pertenece a esta receta: se rechaza.
        if ($prescriptionItem === null) {
            throw DispensationException::prescriptionExceeded('El ítem no pertenece a la prescripción indicada.');
        }

        // Cantidad que el usuario quiere dispensar de este ítem.
        $quantity = (int) $requested['quantity'];

        // Cuánto falta por dispensar según la receta (lo calcula un helper).
        $remaining = $this->remainingToDispense($prescriptionItem, $alreadyDispensed);

        // No se puede dispensar más de lo pendiente. En el detalle del error
        // se informa cuánto faltaba y cuánto se pidió.
        if ($quantity > $remaining) {
            throw DispensationException::prescriptionExceeded(
                'La cantidad supera lo pendiente por dispensar.',
                [$prescriptionItem->id => ["Pendiente: {$remaining}, solicitado: {$quantity}."]]
            );
        }

        // Se reparte la cantidad entre los lotes de ese producto.
        // `?? []` significa "si el producto no tiene lotes, usa lista vacía";
        // en ese caso `takeFromBatches()` lanzará el error de stock insuficiente.
        return $this->takeFromBatches(
            $quantity,
            $stockByProduct[$prescriptionItem->product_id] ?? [],
            $prescriptionItem
        );
    }

    /**
     * Cantidad que falta por dispensar de una línea de receta.
     *
     * @param  array<int|string, mixed>  $alreadyDispensed
     */
    private function remainingToDispense(PrescriptionItem $prescriptionItem, array $alreadyDispensed): int
    {
        // `?? 0` significa "si no hay dato, usa 0" (ítem nunca dispensado).
        $dispensed = (int) ($alreadyDispensed[$prescriptionItem->id] ?? 0);

        // Lo que falta = lo recetado menos lo ya dispensado; `max(0, ...)`
        // evita que quede un número negativo.
        return max(0, (int) $prescriptionItem->quantity - $dispensed);
    }

    /**
     * Toma unidades lote por lote (FEFO) hasta cubrir la cantidad.
     *
     * @param  list<array<string, mixed>>  $batches
     * @return list<array<string, mixed>>
     */
    private function takeFromBatches(int $quantity, array $batches, PrescriptionItem $prescriptionItem): array
    {
        $pending = $quantity;
        $rows = [];

        // Asignación FEFO: se toma de los lotes que vencen primero.
        foreach ($batches as $batch) {
            // Si ya se cubrió la cantidad pedida, deja de recorrer lotes.
            if ($pending <= 0) {
                break;
            }

            // Toma lo máximo posible del lote sin pasarse de lo que falta:
            // si el lote tiene 10 y faltan 6, toma 6.
            $take = min($batch['available'], $pending);

            // Se apunta la extracción. `is_special` se usa más adelante para
            // exigir la autorización de un regente de farmacia.
            $rows[] = [
                'prescription_item_id' => $prescriptionItem->id,
                'batch_id' => $batch['batch_id'],
                'quantity' => $take,
                'is_special' => (bool) $prescriptionItem->product->is_special,
            ];

            // Se resta lo tomado; cuando llega a 0, el bucle termina.
            $pending -= $take;
        }

        // Si tras recorrer todos los lotes sigue faltando, no hay stock
        // suficiente en esta bodega para completar lo pedido.
        if ($pending > 0) {
            throw DispensationException::insufficientStock(
                'No hay stock suficiente en la bodega seleccionada.',
                [$prescriptionItem->id => ["Faltan {$pending} unidades."]]
            );
        }

        return $rows;
    }

    /**
     * Persiste los ítems y descuenta el stock asignado.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     */
    private function persistAllocations(Dispensation $dispensation, Warehouse $warehouse, $rows): void
    {
        $dispensation->items()->createMany(
            $rows->map(fn (array $row): array => [
                'prescription_item_id' => $row['prescription_item_id'],
                'batch_id' => $row['batch_id'],
                'quantity' => $row['quantity'],
            ])->all()
        );

        foreach ($rows as $row) {
            // Update condicional: si el stock ya no alcanza, falla el lote.
            $updated = Stock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('batch_id', $row['batch_id'])
                ->where('quantity', '>=', $row['quantity'])
                ->decrement('quantity', $row['quantity']);

            if ($updated === 0) {
                throw DispensationException::stockChanged(
                    'El stock cambió desde la previsualización. Vuelve a calcular los lotes.'
                );
            }
        }
    }

    /**
     * Total dispensado por cada ítem de prescripción en todas las bodegas.
     *
     * @param  list<int|string>  $itemIds
     * @return array<int|string, mixed>
     */
    private function remainingByItem(array $itemIds): array
    {
        return DispensationItem::query()
            ->whereIn('prescription_item_id', $itemIds)
            ->selectRaw('prescription_item_id, SUM(quantity) as total')
            ->groupBy('prescription_item_id')
            ->pluck('total', 'prescription_item_id')
            ->all();
    }

    /**
     * Stock válido (no vencido) por producto, ordenado por FEFO.
     *
     * Devuelve dos listas:
     * - `valid`: por cada producto, sus lotes ordenados por vencimiento.
     * - `expired`: cuántos lotes vencidos se descartaron de cada producto.
     *
     * FEFO = "lo que vence primero, sale primero": el lote que vence antes va
     * primero en la lista, para que se consuma antes.
     *
     * @param  list<int|string>  $productIds
     * @return array{valid: array<int, list<array<string, mixed>>>, expired: array<int, int>}
     */
    private function allocateFefo(int $warehouseId, array $productIds): array
    {
        // Fecha de hoy como "AAAA-MM-DD" para poder compararla con `expires_on`.
        $today = now()->toDateString();

        // Consulta el stock de esta bodega para esos productos:
        // - `join('batches', ...)` une cada fila de stock con su lote, porque
        //   la fecha de vencimiento y el producto viven en la tabla `batches`.
        // - `quantity > 0` ignora existencias en cero.
        // - `orderBy('expires_on')` pone primero los lotes que vencen antes (FEFO).
        // - `orderBy('batches.id')` desempata usando el lote más antiguo.
        $stocks = Stock::query()
            ->join('batches', 'batches.id', '=', 'stock.batch_id')
            ->where('stock.warehouse_id', $warehouseId)
            ->where('stock.quantity', '>', 0)
            ->whereIn('batches.product_id', $productIds)
            ->orderBy('batches.expires_on')
            ->orderBy('batches.id')
            ->get([
                // Solo las columnas necesarias, con nombres más claros.
                'stock.batch_id',
                'stock.quantity as available',
                'batches.code as batch_code',
                'batches.expires_on',
                'batches.product_id',
            ]);

        // Diccionarios que se van llenando durante el recorrido:
        // - "producto_id => lista de lotes utilizables"
        // - "producto_id => cuántos lotes vencidos se descartaron"
        $valid = [];
        $expiredCounts = [];

        foreach ($stocks as $row) {
            $productId = (int) $row->product_id;

            // `Carbon::parse(...)->toDateString()` deja la fecha como "AAAA-MM-DD".
            $expiresOn = Carbon::parse($row->expires_on)->toDateString();

            // Si el lote ya venció no se puede usar: solo se cuenta para poder
            // avisar después ("hay N lotes vencidos ignorados").
            if ($expiresOn < $today) {
                // `?? 0` significa "si este producto aún no tiene contador,
                // empieza en 0"; luego se suma 1 por este lote vencido.
                $expiredCounts[$productId] = ($expiredCounts[$productId] ?? 0) + 1;
                continue;
            }

            // Lote utilizable: se guarda con los tipos numéricos ya convertidos.
            $valid[$productId][] = [
                'batch_id' => (int) $row->batch_id,
                'batch_code' => $row->batch_code,
                'expires_on' => $expiresOn,
                'available' => (int) $row->available,
            ];
        }

        // Se devuelven ambas listas; quien llama decide qué usar.
        return ['valid' => $valid, 'expired' => $expiredCounts];
    }
}
