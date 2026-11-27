<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\RecuperarContrasenaRequest;
use App\Http\Requests\VerificarCodigoRequest;
use App\Http\Requests\VerificarCodigoPrimerVezRequest;
use App\Http\Requests\PrimerIngresoRequest;
use App\Models\InformacionUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodigoVerificacionMail;
use App\Models\CodigoVerificacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Profesional_has_permisos;
use App\Models\Profesional;
use App\Models\Empresa;
use App\Support\Audit;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::with(['empresa'])->where('estado', 1)->get();
    }

    public function administradores()
    {
        $administradores = User::where('rol', 'Admin')
            ->where('users.estado', 1)
            ->join('informacion_users', 'informacion_users.id', '=', 'users.id_infoUsuario')
            ->select('users.correo', 'informacion_users.*')
            ->get();

        return response()->json([
            "success" => true,
            "data" => $administradores
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $informacionUser = InformacionUser::where('No_document', $request->No_document)->first();

        if($informacionUser){
            return response()->json([
                'success' => false,
                'message' => 'Cédula ya registrada.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            $informacionUser = InformacionUser::create([
                'name' => $request->name,
                'No_document' => $request->No_document,
                'type_doc' => $request->type_doc,
                'celular' => $request->celular,
                'telefono' => $request->telefono ?? null,
                'nacimiento' => $request->nacimiento,
                'direccion' => $request->direccion,
                'municipio' => $request->municipio,
                'departamento' => $request->departamento,
                'barrio' => $request->barrio,
                'zona' => $request->zona,
            ]);

            $empresa = Empresa::first();

            $user = User::create([
                'id_empresa' => $empresa->id,
                'id_infoUsuario' => $informacionUser->id,
                'correo' => $request->correo,
                'contraseña' => Hash::make($request->contraseña),
                'rol' => 'Admin',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Administrador registrado exitosamente.',
                'informacion' => $informacionUser,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al registrar usuario', ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error al registrar usuario'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $user->empresa = $user->Empresa;
        return $user;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $informacionUser = InformacionUser::where('id', $request->id)->first();

        if (!$informacionUser) {
            return response()->json([
                'success' => false,
                'message' => 'Información de usuario no encontrada.',
            ], 404);
        }

        $user = User::where('id_infoUsuario', $informacionUser->id)->first();

        if ($user && $user->correo != $request->correo) {
            $correoExiste = User::where('correo', $request->correo)->first();
            if ($correoExiste) {
                return response()->json([
                    'success' => false,
                    'message' => 'Correo del administrador ya registrado.',
                ], 409);
            }
            $user->correo = $request->correo;
            $user->save();
        }

        $informacionUser->update([
            'name' => $request->name,
            'No_document' => $request->No_document,
            'type_doc' => $request->type_doc,
            'celular' => $request->celular,
            'telefono' => $request->telefono ?? null,
            'nacimiento' => $request->nacimiento,
            'direccion' => $request->direccion,
            'municipio' => $request->municipio,
            'departamento' => $request->departamento,
            'barrio' => $request->barrio,
            'zona' => $request->zona,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Administrador actualizado exitosamente.',
            'informacion' => $informacionUser,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->estado = 0;
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'User desactivado exitosamente.'
        ], 200);
    }

    /**
     * Emite la sesión (token Sanctum + permisos) para un usuario ya validado.
     * Reutilizada por login() y loginCodigoTemporal().
     */
    private function respuestaLogin(User $user, string $accionAuditoria = 'login', string $descripcionAuditoria = 'Login exitoso')
    {
        $user->tokens()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        $tokenResult = $user->createToken('auth_token');

        // Establece la expiración
        $accessToken = $tokenResult->accessToken;
        $accessToken->expires_at = now()->addHours(16);
        $accessToken->save();

        // Obtén el token en texto plano
        $token = $tokenResult->plainTextToken;

        // Obtener información adicional del usuario
        $infoUsuario = InformacionUser::find($user->id_infoUsuario);

        $permisos = [];
        $hasPermisosIndividuales = [];
        $idProfesional = null;

        if ($user->rol === 'Profesional') {
            // Obtener la profesión del usuario
               $profesional = DB::table('profesionals')
                    ->where('id_infoUsuario', $user->id_infoUsuario)
                    ->first();

                if ($profesional) {

                    $idProfesional = $profesional->id;
                    // 1️⃣ Permisos por profesión
                    $permisosProfesion = DB::table('profesions_has_permisos')
                        ->join('secciones', 'profesions_has_permisos.id_seccion', '=', 'secciones.id')
                        ->where('profesions_has_permisos.id_profesion', $profesional->id_profesion)
                        ->pluck('secciones.nombre');

                    // 2️⃣ Permisos individuales activos
                    $permisosIndividuales = DB::table('profesional_has_permisos')
                        ->join('secciones', 'profesional_has_permisos.id_seccion', '=', 'secciones.id')
                        ->where('profesional_has_permisos.id_profesional', $profesional->id)
                        ->where('usado', 0)
                        ->pluck('secciones.nombre');

                    // 3️⃣ Unificar y eliminar duplicados
                    $permisos = $permisosProfesion
                        ->merge($permisosIndividuales)
                        ->unique()
                        ->values();

                    $permisosTemporales = DB::table('profesional_has_permisos')
                        ->join('secciones', 'profesional_has_permisos.id_seccion', '=', 'secciones.id')
                        ->where('id_profesional', $profesional->id)
                        ->where('usado', 0)
                        ->select(
                            'profesional_has_permisos.id as permiso_id',
                            'secciones.nombre',
                            'secciones.id as id_seccion'
                        )
                        ->get();

                    $hasPermisosIndividuales = $permisosTemporales;

                } else {
                    $permisos = collect();
                }

        } elseif (in_array($user->rol, ['Admin', 'SuperAdmin'], true)) {
            $permisos = DB::table('secciones')->pluck('nombre');
        } else {
            $permisos = collect(); // colección vacía
        }


        $permisos = collect($permisos)->merge(config('permisos.base'))->unique()->values();

        Audit::log($accionAuditoria, [
            'user_id' => $user->id,
            'correo' => $user->correo,
            'modelo' => 'users',
            'modelo_id' => $user->id,
            'descripcion' => $descripcionAuditoria,
            'datos' => ['rol' => $user->rol],
        ]);

        return [
            'success' => true,
            'message' => 'Login exitoso',
            'access_token' => $token,
            'user' => [
                'correo' => $user->correo,
                'rol' => $user->rol,
                'usuario' => array_merge($infoUsuario->toArray(), ['id_profesional' => $idProfesional]),
                'permisos' => $permisos
            ],
            'permisosTemporales' => $hasPermisosIndividuales
        ];
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('correo', $request->correo)->first();

        if (!$user) {
            Audit::log('login.fallido', [
                'correo' => $request->correo,
                'descripcion' => 'Correo no registrado',
                'datos' => ['tipo' => 'USER_NOT_FOUND'],
            ]);

            return response()->json([
                'success' => false,
                'type'    => 'USER_NOT_FOUND',
                'message' => 'El correo no se encuentra registrado'
            ], 403);
        }

        if ($user->estado == 0){
            Audit::log('login.fallido', [
                'user_id' => $user->id,
                'correo' => $user->correo,
                'descripcion' => 'Usuario deshabilitado',
                'datos' => ['tipo' => 'USER_DELETE'],
            ]);

            return response()->json([
                'success' => false,
                'type'    => 'USER_DELETE',
                'message' => 'Usuario deshabilitado'
            ], 403);
        }

        $registro = Profesional_has_permisos::where('codigo', $request->contraseña)
            ->where('usado', false)
            ->first();

        if (!Hash::check($request->contraseña, $user->contraseña) && !$registro) {
            Audit::log('login.fallido', [
                'user_id' => $user->id,
                'correo' => $user->correo,
                'modelo' => 'users',
                'modelo_id' => $user->id,
                'descripcion' => 'Contrasena incorrecta',
                'datos' => ['tipo' => 'INVALID_PASSWORD'],
            ]);

            return response()->json([
                'success' => false,
                'type'    => 'INVALID_PASSWORD',
                'message' => 'La contraseña es incorrecta'
            ], 403);
        }

        $payload = $this->respuestaLogin($user);

        if($registro){
            DB::table('profesional_has_permisos')
                ->where('id', $registro->id)
                ->update([
                    'usado' => 1,
                    'updated_at' => now()
                ]);
        }

        return response()->json($payload);

    }

    /**
     * Ingreso con CÓDIGO DE ACCESO TEMPORAL (un solo uso, con expiración).
     * En una sola transacción: valida y consume el código, reemplaza la
     * contraseña y emite el token de sesión. El usuario entra al sistema
     * obligatoriamente con una contraseña nueva.
     *
     * Body: { correo, codigo, nueva_contraseña }
     */
    public function loginCodigoTemporal(Request $request)
    {
        $datos = $request->validate([
            'correo'           => 'required|email',
            'codigo'           => 'required|string',
            'nueva_contraseña' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/'],
        ]);

        $user = User::where('correo', $datos['correo'])->first();

        if (!$user) {
            Audit::log('codigo.temporal.fallido', [
                'correo' => $datos['correo'],
                'descripcion' => 'Correo no registrado al usar código temporal',
                'datos' => ['tipo' => 'USER_NOT_FOUND'],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'El correo no se encuentra registrado'
            ], 403);
        }

        if ($user->estado == 0) {
            Audit::log('codigo.temporal.fallido', [
                'user_id' => $user->id,
                'correo' => $user->correo,
                'descripcion' => 'Usuario deshabilitado al usar código temporal',
                'datos' => ['tipo' => 'USER_DELETE'],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Usuario deshabilitado'
            ], 403);
        }

        // Solo códigos GENERADOS POR UN ADMINISTRADOR (generado_por != null);
        // los códigos de recuperación por correo nunca consumen este flujo.
        $registro = CodigoVerificacion::where('correo', $user->correo)
            ->where('codigo', $datos['codigo'])
            ->whereNotNull('generado_por')
            ->where('usado', false)
            ->where('expira_en', '>', now())
            ->orderByDesc('id')
            ->first();

        if (!$registro) {
            Audit::log('codigo.temporal.fallido', [
                'user_id' => $user->id,
                'correo' => $user->correo,
                'descripcion' => 'Código temporal inválido o expirado',
                'datos' => ['tipo' => 'CODIGO_INVALIDO'],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'El código es inválido o ya expiró. Solicita uno nuevo.'
            ], 401);
        }

        DB::beginTransaction();
        try {
            // Un solo uso: se marca en la misma transacción del cambio de contraseña
            $registro->usado = true;
            $registro->usado_en = now();
            $registro->ip_uso = $request->ip();
            $registro->save();

            $user->contraseña = Hash::make($datos['nueva_contraseña']);
            $user->save();

            $payload = $this->respuestaLogin(
                $user,
                'codigo.temporal.consumido',
                'Login con código temporal y establecimiento de nueva contraseña'
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error en login con código temporal', ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'No se pudo completar el ingreso'], 500);
        }

        return response()->json($payload);
    }

    /**
     * Genera un código de acceso temporal para un usuario (uso único).
     * Solo Admin/SuperAdmin. Vigencia configurable: 15, 30 o 60 minutos.
     *
     * Body: { expiracion_minutos: 15|30|60 }
     */
    public function generarCodigoAccesoTemporal(Request $request, $id)
    {
        $datos = $request->validate([
            'expiracion_minutos' => 'required|integer|in:15,30,60',
        ]);

        $solicitante = $request->user();

        if (!in_array($solicitante->rol, ['Admin', 'SuperAdmin'], true)) {
            Audit::log('codigo.temporal.denegado', [
                'user_id' => $solicitante->id,
                'descripcion' => 'Intento de generar código temporal sin rol autorizado',
                'datos' => ['rol' => $solicitante->rol],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para generar códigos de acceso'
            ], 403);
        }

        // El listado de administradores expone el id de informacion_users;
        // se resuelve por users.id o por id_infoUsuario para cubrir ambos.
        $usuario = User::where('id', $id)->orWhere('id_infoUsuario', $id)->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        if ($usuario->estado == 0) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario está deshabilitado'
            ], 403);
        }

        // Invalida códigos previos sin usar del mismo usuario (solo puede
        // existir un código vigente por correo)
        CodigoVerificacion::where('correo', $usuario->correo)
            ->whereNotNull('generado_por')
            ->where('usado', false)
            ->update(['usado' => true, 'usado_en' => now(), 'ip_uso' => $request->ip()]);

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $registro = CodigoVerificacion::create([
            'correo' => $usuario->correo,
            'codigo' => $codigo,
            'expira_en' => now()->addMinutes((int) $datos['expiracion_minutos']),
            'usado' => false,
            'generado_por' => $solicitante->id,
        ]);

        Audit::log('codigo.temporal.generado', [
            'user_id' => $solicitante->id,
            'correo' => $solicitante->correo,
            'modelo' => 'users',
            'modelo_id' => $usuario->id,
            'descripcion' => 'Código de acceso temporal generado para ' . $usuario->correo,
            'datos' => [
                'expiracion_minutos' => (int) $datos['expiracion_minutos'],
                'expira_en' => (string) $registro->expira_en,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Código de acceso temporal generado',
            'data' => [
                'codigo' => $codigo,
                'expira_en' => $registro->expira_en,
                'generado_por' => $solicitante->id,
            ],
        ], 201);
    }

    public function miPerfil(Request $request)
    {
        $user = $request->user();

        $infoUsuario = InformacionUser::find($user->id_infoUsuario);

        if (!$infoUsuario) {
            return response()->json([
                'success' => false,
                'message' => 'Información de usuario no encontrada.',
            ], 404);
        }

        $respuesta = [
            'success' => true,
            'rol' => $user->rol,
            'correo' => $user->correo,
            'usuario' => $infoUsuario->toArray(),
            'profesional' => null,
        ];

        if ($user->rol === 'Profesional') {
            $profesional = Profesional::with([
                'infoUsuario',
                'profesion',
                'user:id_infoUsuario,correo',
            ])
                ->where('id_infoUsuario', $user->id_infoUsuario)
                ->first();

            $respuesta['profesional'] = $profesional ? $profesional->toArray() : null;

            if ($profesional && $profesional->infoUsuario) {
                $respuesta['usuario'] = $profesional->infoUsuario->toArray();
            }
        }

        return response()->json($respuesta, 200);
    }

    public function verificacion(RecuperarContrasenaRequest $request)
    {

        $usuario = User::where('correo', $request->correo)->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Correo no registrado'
            ], 404);
        }

        $codigo = Str::random(6);

        CodigoVerificacion::create([
            'correo' => $usuario->correo,
            'codigo' => $codigo,
            'expira_en' => Carbon::now()->addMinutes(240)
        ]);

        Mail::to($usuario->correo)->send(new CodigoVerificacionMail($usuario->correo, $codigo));
        Mail::to('homecaresantaisabel@gmail.com')->send(new CodigoVerificacionMail($usuario->correo, $codigo));

        return response()->json([
            'success' => true,
            'message' => 'Correo enviado con código de verificación'
        ]);
    }

    public function verificarCodigo(VerificarCodigoRequest $request)
    {

        $registro = CodigoVerificacion::where('correo', $request->correo)
            ->where('codigo', $request->codigo)
            ->where('usado', false)
            ->where('expira_en', '>', now())
            ->first();

        if (!$registro) {
            return response()->json(['message' => 'Código inválido o expirado'], 401);
        }

        $usuario = User::where('correo', $request->correo)->first();
        $usuario->contraseña = Hash::make($request->contraseña);
        $usuario->save();

        $registro->usado = true;
        $registro->save();

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    }

    public function verificarCodigoPrimerVez(VerificarCodigoPrimerVezRequest $request)
    {

        $correo = CodigoVerificacion::where('codigo', $request->codigo)->first();

        $registro = CodigoVerificacion::where('correo', $correo->correo)
            ->where('codigo', $request->codigo)
            ->where('usado', false)
            ->where('expira_en', '>', now())
            ->first();

        if (!$registro) {
            return response()->json(['message' => 'Código inválido o expirado'], 401);
        }

        $usuario = User::where('correo', $correo->correo)->first();
        $usuario->contraseña = Hash::make($request->contraseña);
        $usuario->save();

        $registro->usado = true;
        $registro->save();

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    }

    public function verificarUsuario(PrimerIngresoRequest $request)
    {

        $usuario = User::where('correo', $request->correo)->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Correo no registrado'
            ], 404);
        }

        if($usuario->contraseña == null){

            return response()->json([
                'success' => true,
                'primer_ingreso' => true,
                'message' => 'Profesional primer ingreso.'
            ]);
        }

        return response()->json([
            'success' => true,
            'primer_ingreso' => false,
            'message' => 'Usuario ya tiene contraseña registrada.'
        ]);

    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.'
        ]);
    }

}
