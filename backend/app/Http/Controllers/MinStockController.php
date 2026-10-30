<?php

namespace App\Http\Controllers;

use App\Models\MinStock;
use Illuminate\Http\Request;

class MinStockController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $minStock = MinStock::query();

            $minStock = $perPage > 0
                ? $minStock->paginate($perPage)
                : $minStock->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'min_stock' => $minStock
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar el stock mínimo: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
