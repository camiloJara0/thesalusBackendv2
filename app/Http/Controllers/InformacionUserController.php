<?php

namespace App\Http\Controllers;

use App\Models\InformacionUser;
use App\Models\User;
use Illuminate\Http\Request;

class InformacionUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $informacionUser = InformacionUser::where('estado', 1)->get();

        return response()->json([
            "success" => true,
            "data" => $informacionUser
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $informacionUser = InformacionUser::create([
            'id_usuario' => $request->id_usuario,
            'name' => $request->name,
            'No_document' => $request->No_document,
            'type_doc' => $request->type_doc,
            'celular' => $request->celular,
            'telefono' => $request->telefono ?? 0,
            'nacimiento' => $request->nacimiento,
            'direccion' => $request->direccion,
            'municipio' => $request->municipio,
            'departamento' => $request->departamento,
            'barrio' => $request->barrio,
            'zona' => $request->zona,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Información del usuario creada exitosamente.',
            'data' => $informacionUser
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InformacionUser  $informacionUser
     * @return \Illuminate\Http\Response
     */
    public function show(InformacionUser $informacionUser)
    {
        return $informacionUser;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\InformacionUser  $informacionUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InformacionUser $informacionUser)
    {
        // 1️⃣ Buscar o crear el usuario
        $informacionUser = InformacionUser::where('id', $request->id)->first();
        $user = $informacionUser ? User::where('id_infoUsuario', $request->id)->first() : null;

        if($user->correo != $request->correo){
            $correo = User::where('correo', $request->correo)->first();
            if($correo){
                return response()->json([
                    'success' => false,
                    'message' => 'Correo del administrador ya registrado.',
                ], 409);
            }

            $user->correo = $request->correo;
            $user->save();
        }
        if($informacionUser){
            // 2️⃣ Guardar información adicional en InformacionUser
            $informacionUser->name = $request->name;
            $informacionUser->No_document = $request->No_document;
            $informacionUser->type_doc = $request->type_doc;
            $informacionUser->celular = $request->celular;
            $informacionUser->telefono = $request->telefono ?? 0;
            $informacionUser->nacimiento = $request->nacimiento;
            $informacionUser->direccion = $request->direccion;
            $informacionUser->municipio = $request->municipio;
            $informacionUser->departamento = $request->departamento;
            $informacionUser->barrio = $request->barrio;
            $informacionUser->zona = $request->zona;
            $informacionUser->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Administrador actualizado exitosamente.',
            'informacion' => $informacionUser,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InformacionUser  $informacionUser
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, InformacionUser $informacionUser)
    {
        $user = User::where('id_infoUsuario', $request->id)->first();

        $user->estado = 0;
        $user->save();

        // 4️⃣ Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Administrador desactivado exitosamente.',
        ], 200);
    }
}
