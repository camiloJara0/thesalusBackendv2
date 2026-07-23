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
use App\Models\Empresa;


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

    public function login(LoginRequest $request)
    {
        $user = User::where('correo', $request->correo)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'type'    => 'USER_NOT_FOUND',
                'message' => 'El correo no se encuentra registrado'
            ], 403);
        }

        if ($user->estado == 0){
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
            return response()->json([
                'success' => false,
                'type'    => 'INVALID_PASSWORD',
                'message' => 'La contraseña es incorrecta'
            ], 403);
        }

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

        } elseif ($user->rol == 'Admin') {
            $permisos = DB::table('secciones')->pluck('nombre');
        } else {
            $permisos = collect(); // colección vacía
        }


        if($registro){
            DB::table('profesional_has_permisos')
                ->where('id', $registro->id)
                ->update([
                    'usado' => 1,
                    'updated_at' => now()
                ]);
        }

        return response()->json([
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
        ]);

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

}
