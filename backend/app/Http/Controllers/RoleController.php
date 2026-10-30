<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $roles = Role::query();

            $roles = $perPage > 0
                ? $roles->paginate($perPage)
                : $roles->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'roles' => $roles
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los roles: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
