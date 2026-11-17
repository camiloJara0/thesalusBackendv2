<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Support\Audit;
use Illuminate\Http\Request;

class ErroresController extends Controller
{
    public function index(Request $request)
    {
        $query = ErrorLog::query();

        $this->aplicarFiltros($query, $request);

        $errores = $query
            ->orderByDesc('occurred_at')
            ->paginate(min((int) $request->input('por_pagina', 50), 100));

        return response()->json([
            'success' => true,
            'data' => $errores,
        ]);
    }

    public function show($id)
    {
        $error = ErrorLog::find($id);

        if (!$error) {
            return response()->json([
                'success' => false,
                'message' => 'Error no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $error,
        ]);
    }

    public function destroy(Request $request)
    {
        $query = ErrorLog::query();
        $this->aplicarFiltros($query, $request);

        if (!$request->filled('desde') && !$request->filled('hasta') && !$request->filled('level') && !$request->filled('fingerprint')) {
            return response()->json([
                'success' => false,
                'message' => 'Debe indicar al menos un filtro (desde, hasta, level o fingerprint)',
            ], 422);
        }

        $eliminados = $query->delete();

        Audit::log('errores.purga', [
            'modelo' => 'error_logs',
            'descripcion' => "Purga de error_logs ({$eliminados} registros)",
            'datos' => [
                'level' => $request->level,
                'fingerprint' => $request->fingerprint,
                'desde' => $request->desde,
                'hasta' => $request->hasta,
                'eliminados' => $eliminados,
            ],
        ]);

        return response()->json([
            'success' => true,
            'eliminados' => $eliminados,
        ]);
    }

    private function aplicarFiltros($query, Request $request): void
    {
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('fingerprint')) {
            $query->where('fingerprint', $request->fingerprint);
        }

        if ($request->filled('desde')) {
            $query->where('occurred_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->where('occurred_at', '<=', $request->hasta . ' 23:59:59');
        }
    }
}
