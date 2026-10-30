<?php

namespace App\Http\Controllers;

use App\Models\DispensationItem;
use Illuminate\Http\Request;

class DispensationItemController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $dispensationItems = DispensationItem::query();

            $dispensationItems = $perPage > 0
                ? $dispensationItems->paginate($perPage)
                : $dispensationItems->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'dispensation_items' => $dispensationItems
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los ítems de dispensación: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
