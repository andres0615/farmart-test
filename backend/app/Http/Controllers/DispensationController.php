<?php

namespace App\Http\Controllers;

use App\Models\Dispensation;
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
}
