<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $warehouses = Warehouse::query();

            $warehouses = $perPage > 0
                ? $warehouses->paginate($perPage)
                : $warehouses->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'warehouses' => $warehouses
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los almacenes: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
