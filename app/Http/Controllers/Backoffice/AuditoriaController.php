<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('correo')) {
            $query->where('correo', 'like', '%' . $request->correo . '%');
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('desde')) {
            $query->where('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->where('created_at', '<=', $request->hasta . ' 23:59:59');
        }

        $registros = $query
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('por_pagina', 50), 100));

        return response()->json([
            'success' => true,
            'data' => $registros,
        ]);
    }

    public function show($id)
    {
        $registro = AuditLog::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de auditoria no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $registro,
        ]);
    }
}
