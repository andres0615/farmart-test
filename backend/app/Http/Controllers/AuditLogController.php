<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->integer('per_page');

            $auditLogs = AuditLog::query();

            $auditLogs = $perPage > 0
                ? $auditLogs->paginate($perPage)
                : $auditLogs->get();

            $responseData = [
                'success' => true,
                'data' => [
                    'audit_logs' => $auditLogs
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            // Si hay errores generales
            $errorMessage = 'Error al consultar los registros de auditoría: ' . $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }
}
