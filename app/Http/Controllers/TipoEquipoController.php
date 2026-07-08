<?php

namespace App\Http\Controllers;

use App\Models\Tipo_equipo;
use Illuminate\Http\Request;

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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
