<?php

namespace App\Http\Controllers;

use App\Models\Tipo_equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoEquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tipos_equipos = Tipo_equipo::get();
        return response()->json([
            'success' => true,
            'data' => $tipos_equipos
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:tipo_equipos,nombre',
            'descripcion' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $tipo_equipo = Tipo_equipo::create($validated);

            DB::commit();
            return response()->json(['success' => true, 'data' => $tipo_equipo, 'message' => 'Tipo de equipo creado'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al crear tipo de equipo'], 500);
        }

        return response()->json([
            'sucess' => true,
            'data' => $tipo_equipo
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function show(Tipo_equipo $tipo_equipo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function edit(Tipo_equipo $tipo_equipo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tipo_equipo $tipo_equipo)
    {
        $tipo_equipo->update($request->all());
        return $tipo_equipo;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tipo_equipo $tipo_equipo)
    {
        //
    }
}
