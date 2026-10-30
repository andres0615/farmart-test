<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $prescriptions = Prescription::query();

            $prescriptions = $perPage > 0
                ? $prescriptions->paginate($perPage)
                : $prescriptions->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'prescriptions' => $prescriptions
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar las recetas: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
