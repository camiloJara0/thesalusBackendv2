<?php

namespace App\Http\Controllers;

use App\Models\Profesion;
use App\Http\Requests\StorePermisosRequest;
use App\Http\Requests\SolicitarPermisoRequest;
use App\Http\Requests\AprobarPermisoRequest;
use App\Http\Requests\VerificarPermisosRequest;
use App\Http\Requests\ConsumirPermisoRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Models\Profesionals;
use App\Models\Profesional_has_permisos;
use App\Models\Secciones;
use Illuminate\Http\Request;
use App\Mail\SolicitudPermisoMail;
use App\Mail\PermisoAprobadoMail;

class ProfesionalHasPermisosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $permiso = Profesion::where('estado', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $permiso
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePermisosRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1️⃣ Crear nuevo permiso

            if (!empty($request->permisos) && is_array($request->permisos)) {
                // 1️⃣ Obtener profesional
                $profesional = DB::table('profesionals')
                    ->where('id', $request->id_profesional)
                    ->first();

                if (!$profesional) {
                    throw new \Exception("Profesional no encontrado");
                }

                // 2️⃣ Permisos que ya tiene por profesión
                $permisosProfesion = DB::table('profesions_has_permisos')
                    ->where('id_profesion', $profesional->id_profesion)
                    ->pluck('id_seccion')
                    ->toArray();

                // 3️⃣ Obtener IDs de las secciones solicitadas
                $seccionesSolicitadas = DB::table('secciones')
                    ->whereIn('nombre', $request->permisos)
                    ->pluck('id')
                    ->toArray();

                // 4️⃣ Filtrar solo los que NO tiene por profesión
                $permisosAInsertar = array_diff($seccionesSolicitadas, $permisosProfesion);

                // 5️⃣ Insertar solo los válidos
                foreach ($permisosAInsertar as $idSeccion) {
                    DB::table('profesional_has_permisos')->insert([
                        'id_profesional' => $request->id_profesional,
                        'id_seccion'     => $idSeccion,
                        'fecha_inicio'   => now(),
                        'fecha_fin'      => $request->fecha_fin ?? null,
                    ]);
                }
            };

            DB::commit();

            // 3️⃣ Retornar respuesta
            return response()->json([
                'success' => true,
                'message' => 'Permisos para profesional creadas exitosamente.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al crear permisos al profesional (store)', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear los permisos al profesional.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Profesion  $profesion
     * @return \Illuminate\Http\Response
     */
    public function show(Profesion $profesion)
    {
        return $profesion;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Profesion  $profesion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Profesion $profesion)
    {
        DB::beginTransaction();

        try {
            $profesional = Profesional::where('id', $request->id_profesional)->first();

            $permisosIds = [];
            if (!empty($request->permisos) && is_array($request->permisos)) {
                $permisosIds = Secciones::whereIn('nombre', $request->permisos)->pluck('id')->toArray();
            }

            $profesional->permisos()->sync($permisosIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permisos para profesional actualizados exitosamente.',
                'data' => $profesion
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar permisos del profesional', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los permisos del profesional.',
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Profesion  $profesion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Profesion $profesion)
    {
        $profesion->estado = 0;
        $profesion->save();
        return response()->json([
            'success' => true,
            'message' => 'Profesión desactivada exitosamente.'
        ], 200);

    }

    public function solicitarPermiso(SolicitarPermisoRequest $request)
    {
        DB::beginTransaction();

        try {

            $seccion = Secciones::where('id', $request->id_seccion)->first();
            $profesional = DB::table('profesionals')
                ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
                ->where('profesionals.id', $request->id_profesional)
                ->select('profesionals.*', 'informacion_users.*')
                ->first();

            if (!$seccion || !$profesional) {
                throw new \Exception("Sección o profesional no encontrados");
            }

            $token = Str::uuid();

            DB::table('solicitud_permisos')->insert([
                'id_profesional'     => $request->id_profesional,
                'id_seccion'         => $request->id_seccion,
                'token_aprobacion'   => $token,
                'estado'             => 'pendiente',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // Enviar correo al admin
            $link = rtrim(env('APP_URL'), '/') . "/aprobar-permiso?token={$token}";

            Mail::to('camilojara0000@gmail.com')->send(new SolicitudPermisoMail($link, $profesional, $seccion));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud enviada al administrador'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            \Log::error('Error al solicitar permiso', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud de permiso',
            ]);
        }
    }

    public function aprobarPermiso(AprobarPermisoRequest $request)
    {
        DB::beginTransaction();

        try {

            $solicitud = DB::table('solicitud_permisos')
                ->where('token_aprobacion', $request->token)
                ->where('estado', 'pendiente')
                ->first();

            if (!$solicitud) {
                throw new \Exception("Solicitud inválida o ya usada");
            }

            $codigo = Str::random(6);

            DB::table('profesional_has_permisos')->insert([
                'id_profesional' => $solicitud->id_profesional,
                'id_seccion'     => $solicitud->id_seccion,
                'fecha_inicio'   => now(),
                'usado'          => 0,
                'codigo'         => $codigo,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('solicitud_permisos')
                ->where('id', $solicitud->id)
                ->update([
                    'estado' => 'aprobado'
                ]);

            $profesional = DB::table('profesionals')
                ->join('users', 'profesionals.id_infoUsuario', '=', 'users.id_infoUsuario')
                ->where('profesionals.id', $solicitud->id_profesional)
                ->select('profesionals.*', 'Users.*')
                ->first();

            Mail::to($profesional->correo)->send(new PermisoAprobadoMail($codigo, $profesional->correo));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permiso otorgado correctamente'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            \Log::error('Error al aprobar permiso', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la aprobación del permiso',
            ]);
        }
    }

    public function verificarPermisos(VerificarPermisosRequest $request)
    {
        $permisos = DB::table('profesional_has_permisos')
            ->join('secciones', 'profesional_has_permisos.id_seccion', '=', 'secciones.id')
            ->where('id_profesional', $request->id_profesional)
            ->where('usado', 0)
            ->select(
                'profesional_has_permisos.id as permiso_id',
                'secciones.nombre',
                'secciones.id as id_seccion'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permisos
        ]);
    }

    public function consumirPermiso(ConsumirPermisoRequest $request)
    {
        DB::table('profesional_has_permisos')
            ->where('id', $request->permiso_id)
            ->update([
                'usado' => 1,
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true
        ]);
    }
}
