<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['empresa', 'infoUsuario']);

        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }

        if ($request->filled('buscar')) {
            $query->where('correo', 'like', '%' . $request->buscar . '%');
        }

        $usuarios = $query
            ->withCount([
                'tokens as tokens_activos' => fn ($q) => $q->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                }),
            ])
            ->orderBy('id')
            ->paginate(min((int) $request->input('por_pagina', 50), 100));

        $usuarios->getCollection()->transform(function (User $user) {
            return [
                'id' => $user->id,
                'correo' => $user->correo,
                'rol' => $user->rol,
                'estado' => $user->estado,
                'nombre' => $user->infoUsuario?->name,
                'empresa' => $user->empresa?->nombre,
                'tokens_activos' => (int) $user->tokens_activos,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $usuarios,
        ]);
    }

    public function show($id)
    {
        $user = User::with(['empresa', 'infoUsuario'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $tokens = $user->tokens()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'name', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'correo' => $user->correo,
                'rol' => $user->rol,
                'estado' => $user->estado,
                'nombre' => $user->infoUsuario?->name,
                'empresa' => $user->empresa?->nombre,
                'permisos_efectivos' => $this->permisosDe($user),
                'tokens_recientes' => $tokens,
            ],
        ]);
    }

    public function purgarTokens($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $eliminados = $user->tokens()->delete();

        Audit::log('usuarios.tokens_purgados', [
            'user_id' => $user->id,
            'correo' => $user->correo,
            'modelo' => 'users',
            'modelo_id' => $user->id,
            'descripcion' => "Purga de {$eliminados} tokens",
            'datos' => ['tokens_eliminados' => $eliminados],
        ]);

        return response()->json([
            'success' => true,
            'tokens_eliminados' => $eliminados,
        ]);
    }

    private function permisosDe(User $user): array
    {
        $base = config('permisos.base', []);

        if (in_array($user->rol, ['Admin', 'SuperAdmin'], true)) {
            return array_values(array_unique(array_merge(
                DB::table('secciones')->pluck('nombre')->all(),
                $base
            )));
        }

        if ($user->rol !== 'Profesional') {
            return $base;
        }

        $profesional = DB::table('profesionals')
            ->where('id_infoUsuario', $user->id_infoUsuario)
            ->first();

        if (!$profesional) {
            return $base;
        }

        $porProfesion = DB::table('profesions_has_permisos')
            ->join('secciones', 'profesions_has_permisos.id_seccion', '=', 'secciones.id')
            ->where('profesions_has_permisos.id_profesion', $profesional->id_profesion)
            ->pluck('secciones.nombre');

        $individuales = DB::table('profesional_has_permisos')
            ->join('secciones', 'profesional_has_permisos.id_seccion', '=', 'secciones.id')
            ->where('profesional_has_permisos.id_profesional', $profesional->id)
            ->where('usado', 0)
            ->pluck('secciones.nombre');

        return array_values(array_unique(array_merge(
            $porProfesion->all(),
            $individuales->all(),
            $base
        )));
    }
}
