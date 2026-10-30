<?php

namespace App\Http\Controllers;

use App\Models\PrescriptionItem;
use Illuminate\Http\Request;

class PrescriptionItemController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $prescriptionItems = PrescriptionItem::query();

            $prescriptionItems = $perPage > 0
                ? $prescriptionItems->paginate($perPage)
                : $prescriptionItems->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'prescription_items' => $prescriptionItems
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los ítems de receta: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
