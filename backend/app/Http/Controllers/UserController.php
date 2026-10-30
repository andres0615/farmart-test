<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $users = User::query();

            $users = $perPage > 0
                ? $users->paginate($perPage)
                : $users->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'users' => $users
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los usuarios: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
