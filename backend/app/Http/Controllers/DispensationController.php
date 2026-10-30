<?php

namespace App\Http\Controllers;

use App\Exceptions\PrescriptionExpiredException;
use App\Http\Requests\PreviewDispensationRequest;
use App\Http\Requests\StoreDispensationRequest;
use App\Models\Dispensation;
use App\Models\Prescription;
use App\Models\Warehouse;
use App\Services\DispensationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class DispensationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $dispensations = Dispensation::query();

            $dispensations = $perPage > 0
                ? $dispensations->paginate($perPage)
                : $dispensations->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'dispensations' => $dispensations
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar las dispensaciones: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    public function store(
        StoreDispensationRequest $request,
        DispensationService $service
    ) {
        try {
            $prescription = Prescription::with(['patient', 'items.product'])
                ->findOrFail($request->integer('prescription_id'));
            $warehouse = Warehouse::findOrFail($request->integer('warehouse_id'));

            $dispensation = $service->create(
                $prescription,
                $warehouse,
                $request->user(),
                $request->input('items', []),
                $request->filled('authorized_by') ? $request->integer('authorized_by') : null,
                (string) $request->header('Idempotency-Key'),
            );

            $dispensation->loadMissing('items');

            return response()->json([
                'success' => true,
                'data' => [
                    'dispensation' => $dispensation->toArray(),
                ],
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la prescripción o la bodega indicada.',
            ], 404);
        } catch (PrescriptionExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La prescripción no está vigente.',
                'code' => 'PRESCRIPTION_EXPIRED',
            ], 422);
        }
    }

    public function preview(
        PreviewDispensationRequest $request,
        DispensationService $service
    ) {
        try {
            $prescription = Prescription::with(['patient', 'items.product'])
                ->findOrFail($request->integer('prescription_id'));
            $warehouse = Warehouse::findOrFail($request->integer('warehouse_id'));

            return response()->json([
                'success' => true,
                'data' => $service->preview($prescription, $warehouse),
            ]);
        } catch (PrescriptionExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La prescripción no está vigente.',
                'code' => 'PRESCRIPTION_EXPIRED',
            ], 422);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al calcular la dispensación: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $dispensation = Dispensation::query()
                ->with([
                    'prescription.patient',
                    'warehouse',
                    'dispensedBy:id,name',
                    'authorizedBy:id,name',
                    'items.batch',
                    'items.prescriptionItem.product',
                ])
                ->withCount('items')
                ->find($id);

            if ($dispensation === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dispensación no encontrada',
                ], 404);
            }

            $payload = $this->dispensationPayload($dispensation);

            return response()->json([
                'success' => true,
                'data' => [
                    'dispensation' => $payload,
                ],
            ]);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar la dispensación: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    /**
     * Se arma a mano para evitar que las relaciones `dispensed_by` y
     * `authorized_by` sobrescriban los IDs numéricos que usa el frontend.
     *
     * @return array<string, mixed>
     */
    private function dispensationPayload(Dispensation $dispensation): array
    {
        $payload = $dispensation->attributesToArray();
        $payload['prescription'] = $dispensation->prescription?->toArray();
        $payload['warehouse'] = $dispensation->warehouse?->toArray();
        $payload['dispensed_by_user'] = $dispensation->dispensedBy?->only(['id', 'name']);
        $payload['authorized_by_user'] = $dispensation->authorizedBy?->only(['id', 'name']);
        $payload['items'] = $dispensation->items->toArray();
        $payload['items_count'] = $dispensation->items_count;

        return $payload;
    }
}
