<?php

namespace App\Http\Controllers;

use App\Models\Profesional;
use App\Models\InformacionUser;
use App\Models\Profesion;
use App\Models\User;
use App\Models\Cita;
use App\Models\Empresa;
use App\Http\Requests\StoreProfesionalRequest;
use App\Http\Requests\UpdateProfesionalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodigoVerificacionMail;
use App\Models\CodigoVerificacion;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;

class ProfesionalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $profesionales = Profesional::with('infoUsuario', 'profesion', 'user:id_infoUsuario,correo')
        ->where('profesionals.estado', 1)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $profesionales
        ], 200);
    }

    public function profesionalesInactivos()
    {
        $profesionales = Profesional::with('infoUsuario', 'profesion', 'user:id_infoUsuario,correo')
        ->where('profesionals.estado', 0)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $profesionales
        ], 200);
    }

    public function traeProfesionales()
    {
        $profesionales = Profesional::select('profesionals.*', 'users.correo', 'profesions.nombre as nombreProfesion')
        ->join('users', 'users.id_infoUsuario', '=', 'profesionals.id_infoUsuario')
        ->join('profesions', 'profesions.id', '=', 'profesionals.id_profesion')
        ->where('profesionals.estado', 1)
        ->get();
        
        $informacionUsers = InformacionUser::get();

        return response()->json([
            'success' => true,
            'profesionales' => $profesionales,
            'informacionUsers' => $informacionUsers,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProfesionalRequest $request)
    {
        $informacionUser = InformacionUser::where('No_document', $request->No_document)->first();
        $usuario = $informacionUser ? User::where('id_infoUsuario', $informacionUser->id)->first() : null;
        $correo = User::where('correo', $request->correo)->first();
        if($correo){
            return response()->json([
                'success' => false,
                'message' => 'Correo del profesional ya registrado.',
            ], 409);
        }
        if($informacionUser){
            return response()->json([
                'success' => true,
                'message' => 'Cédula ya registrada.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            if(!$informacionUser){
                $informacionUser = new InformacionUser();
                $informacionUser->name = $request->name;
                $informacionUser->No_document = $request->No_document;
                $informacionUser->type_doc = $request->type_doc;
                $informacionUser->celular = $request->celular;
                $informacionUser->telefono = $request->telefono ?? null;
                $informacionUser->nacimiento = $request->nacimiento;
                $informacionUser->direccion = $request->direccion;
                $informacionUser->municipio = $request->municipio;
                $informacionUser->departamento = $request->departamento;
                $informacionUser->barrio = $request->barrio;
                $informacionUser->zona = $request->zona;
                $informacionUser->save();
            }

            $profesional = new Profesional();
            $profesional->id_infoUsuario = $informacionUser->id;
            $profesional->id_profesion = $request->id_profesion;
            $profesional->zona_laboral = $request->zona_laboral;
            $profesional->departamento_laboral = $request->departamento_laboral;
            $profesional->municipio_laboral = $request->municipio_laboral;

            $selloPath = null;
            if ($request->hasFile('selloFile') && $request->file('selloFile')->isValid()) {
                $file = $request->file('selloFile');
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $folder = 'profesionales/sellos';
                $path = $file->storeAs($folder, $filename, 'public');
                $selloPath = $path;
            }

            $profesional->sello = $selloPath;
            $profesional->save();

            $correoEnviado = null;

            if(!$usuario){
                $empresa = Empresa::first();
                $usuario = new User();
                $usuario->id_empresa = $empresa->id;
                $usuario->id_infoUsuario = $informacionUser->id;;
                $usuario->correo = $request->correo;
                $usuario->contraseña = null;
                $usuario->rol = 'Profesional';
                $usuario-> save();

                $codigo = Str::random(6);

                CodigoVerificacion::create([
                    'correo' => $usuario->correo,
                    'codigo' => $codigo,
                    'expira_en' => Carbon::now()->addMinutes(240)
                ]);

                $usuarioCreador = User::where('id_infoUsuario', $request->id_correoCreador)->first();
                $correoEnviado = ['correo' => $usuario->correo, 'codigo' => $codigo, 'correoCreador' => $usuarioCreador->correo ?? null];
            }

            DB::commit();

            if ($correoEnviado) {
                try {
                    Mail::to($correoEnviado['correo'])->send(new CodigoVerificacionMail($correoEnviado['correo'], $correoEnviado['codigo']));
                    if(($correoEnviado['correoCreador'] ?? null) === 'admin@demo.com') {
                        Mail::to('cata61779@gmail.com')->send(new CodigoVerificacionMail($correoEnviado['correo'], $correoEnviado['codigo']));
                        Mail::to('homecaresantaisabel@gmail.com')->send(new CodigoVerificacionMail($correoEnviado['correo'], $correoEnviado['codigo']));
                    } else if ($correoEnviado['correoCreador']) {
                        Mail::to($correoEnviado['correoCreador'])->send(new CodigoVerificacionMail($correoEnviado['correo'], $correoEnviado['codigo']));
                    }
                } catch (\Exception $e) {
                    \Log::error('Error enviando correo de verificación', ['exception' => $e]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Profesional registrado exitosamente.',
                'informacion' => $informacionUser,
                'profesional' => $profesional
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al registrar Profesional', ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error al registrar Profesional'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Profesional  $profesional
     * @return \Illuminate\Http\Response
     */
    public function show(Profesional $profesional)
    {
        return $profesional;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Profesional  $profesional
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProfesionalRequest $request, Profesional $profesional)
    {
        $informacionUser = InformacionUser::where('id', $request->id_infoUsuario)->first();
        $usuario = User::where('id_infoUsuario', $informacionUser->id)->first();

        if($usuario->correo != $request->correo){
            $correo = User::where('correo', $request->correo)->first();
            if($correo){
                return response()->json([
                    'success' => false,
                    'message' => 'Correo del profesional ya registrado.',
                ], 409);
            }
        }

        DB::beginTransaction();

        try {
            $correoEnviado = null;

            if($usuario->correo != $request->correo){
                $usuario->correo = $request->correo;
                $usuario->save();

                $codigo = Str::random(6);

                CodigoVerificacion::create([
                    'correo' => $usuario->correo,
                    'codigo' => $codigo,
                    'expira_en' => Carbon::now()->addMinutes(240)
                ]);

                $correoEnviado = ['correo' => $usuario->correo, 'codigo' => $codigo];
            }

            if ($informacionUser) {
                    $informacionUser->name = $request->name;
                    $informacionUser->No_document = $request->No_document;
                    $informacionUser->type_doc = $request->type_doc;
                    $informacionUser->celular = $request->celular;
                    $informacionUser->telefono = $request->telefono ?? null;
                    $informacionUser->nacimiento = $request->nacimiento;
                    $informacionUser->direccion = $request->direccion;
                    $informacionUser->municipio = $request->municipio;
                    $informacionUser->departamento = $request->departamento;
                    $informacionUser->barrio = $request->barrio;
                    $informacionUser->zona = $request->zona;
                    $informacionUser->save();
            }

                $profesional->id_profesion = $request->id_profesion;
                $profesional->zona_laboral = $request->zona_laboral;
                $profesional->departamento_laboral = $request->departamento_laboral;
                $profesional->municipio_laboral = $request->municipio_laboral;
                $profesional->estado = $request->estado;

            if ($request->hasFile('selloFile') && $request->file('selloFile')->isValid()) {
                $file     = $request->file('selloFile');
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path     = $file->storeAs('profesionales/sellos', $filename, 'public');
                $profesional->sello = $path;
            }
                $profesional->save();

            DB::commit();

            if ($correoEnviado) {
                try {
                    Mail::to($correoEnviado['correo'])->send(new CodigoVerificacionMail($correoEnviado['correo'], $correoEnviado['codigo']));
                } catch (\Exception $e) {
                    \Log::error('Error enviando correo de verificación', ['exception' => $e]);
                }
            }

            return response()->json([
                'success'     => true,
                'message'     => 'Datos del profesional actualizados exitosamente.',
                'informacion' => $informacionUser,
                'profesional' => $profesional,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar Profesional', ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar Profesional'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Profesional  $profesional
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Profesional $profesional)
    {
        DB::beginTransaction();

        try {
            $user = User::where('id_infoUsuario', $profesional->id_infoUsuario)->first();
            if ($user) {
                $user->estado = 0;
                $user->save();
            }

            $profesional->estado = 0;
            $profesional->save();

            Cita::where('id_medico', $profesional->id)
                ->where('estado', 'Inactiva')
                ->update([
                    'estado' => 'cancelada',
                    'motivo_cancelacion' => 'Profesional eliminado',
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profesional deshabilitado exitosamente.',
                'data' => $profesional
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al eliminar Profesional', ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar Profesional'], 500);
        }
    }
}
