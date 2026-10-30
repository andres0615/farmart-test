<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $transfers = Transfer::query();

            $transfers = $perPage > 0
                ? $transfers->paginate($perPage)
                : $transfers->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'transfers' => $transfers
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar las transferencias: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
