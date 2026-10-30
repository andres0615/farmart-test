<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $stockMovements = StockMovement::query();

            $stockMovements = $perPage > 0
                ? $stockMovements->paginate($perPage)
                : $stockMovements->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'stock_movements' => $stockMovements
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los movimientos de stock: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
