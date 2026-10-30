<?php

namespace App\Http\Controllers;

use App\Models\TransferItem;
use Illuminate\Http\Request;

class TransferItemController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $transferItems = TransferItem::query();

            $transferItems = $perPage > 0
                ? $transferItems->paginate($perPage)
                : $transferItems->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'transfer_items' => $transferItems
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los ítems de transferencia: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
