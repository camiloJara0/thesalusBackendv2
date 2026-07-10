<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Plan_manejo_procedimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CitaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cita = Cita::with(['paciente.infoUsuario', 'profesional.infoUsuario', 'servicio', 'analisis'])->get();

        return response()->json([
            'success' => true,
            'data' => $cita
        ]);
    }

    public function citasHoy()
    {
        $inicio = now()->startOfMonth()->toDateString();
        $fin = now()->endOfMonth()->toDateString();

        $citas = Cita::with(['paciente.infoUsuario', 'profesional.infoUsuario', 'servicio'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->limit(200)
            ->get();

        return response()->json(['success' => true, 'data' => $citas]);
    }

    public function citasPorRango(Request $request)
    {
        $inicio = $request->input('inicio'); // ej: 2026-03-01
        $fin = $request->input('fin');       // ej: 2026-03-31

        $citas = Cita::with(['paciente.infoUsuario', 'profesional.infoUsuario', 'servicio'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->get();

        return response()->json(['success' => true, 'data' => $citas]);
    }

    public function citasPaginadas(Request $request)
    {
        $ultimoId  = $request->input('ultimo_id', 0);
        $porPagina = $request->input('por_pagina', 50);

        $query = Cita::with([
            'paciente.infoUsuario',
            'profesional.infoUsuario',
            'servicio'
        ]);

        if ($ultimoId > 0) {
            $query->where('id', '<', $ultimoId);
        }

        $citas = $query
            ->orderBy('id', 'desc')
            ->limit($porPagina)
            ->get();

        return response()->json([
            'success' => true, 
            'data' => $citas,
        ]);
    }

    public function citasFiltradas(Request $request)
    {
        $query = Cita::with(['paciente.infoUsuario', 'profesional.infoUsuario', 'servicio']);

        if ($request->filled('estado')) {
            $query->where('citas.estado', 'like', "%{$request->estado}%");
        }
        if ($request->filled('name_medico')) {
            $query->whereHas('profesional.infoUsuario', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->name_medico}%");
            });
        }
        if ($request->filled('servicio')) {
            $query->whereHas('servicio', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->servicio}%");
            });
        }
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('citas.fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }
        if ($request->filled('fecha_mes')) {
            $query->whereMonth('citas.fecha', $request->fecha_mes);
        }

        if ($request->filled('fecha_año')) {
            $query->whereYear('citas.fecha', $request->fecha_año);
        }

        $citas = $query->limit(200)->get(); // carga moderada

        return response()->json(['success' => true, 'data' => $citas]);
    }

    public function filtrosCitas()
    {
        $años = DB::table('citas')
            ->select(DB::raw('YEAR(fecha) as año'))
            ->distinct()
            ->pluck('año');
        $medicos = DB::table('profesionals')
            ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
            ->select('informacion_users.name')
            ->distinct()
            ->pluck('name');
        $servicios = DB::table('servicio')->select('name')->distinct()->pluck('name');

        return response()->json([
            'success' => true,
            'data' => [
                'años' => $años,
                'medicos' => $medicos,
                'servicios' => $servicios,
            ]
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
        $plan = null;

        if ($request->procedimiento) {
            // Validar si ya existe el procedimiento para el paciente
            $plan = Plan_manejo_procedimiento::where('id_paciente', $request->id_paciente)
                ->where('codigo', $request->codigo)
                ->first();

            if ($plan) {
                // Si existe, sumar los días_asignados
                $plan->dias_asignados += 1;
                $plan->save();
            } else {
                // Si no existe, crear nuevo
                $plan = Plan_manejo_procedimiento::create([
                    'id_paciente'    => $request->id_paciente,
                    'id_medico'      => $request->id_medico,
                    'procedimiento'  => $request->procedimiento,
                    'codigo'         => $request->codigo,
                    'dias_asignados' => $request->dias_asignados ?? 1,
                ]);
            }
        }

        $cita = new Cita();
        $cita->id_paciente        = $request->id_paciente;
        $cita->id_medico          = $request->id_medico;
        $cita->id_analisis        = null;
        $cita->id_servicio        = $request->id_servicio;
        $cita->motivo             = $request->motivo;
        $cita->fecha              = $request->fecha;
        $cita->fechaHasta         = $request->fechaHasta;
        $cita->hora               = $request->hora ?? '00:00:00';
        $cita->estado             = 'Inactiva';
        $cita->motivo_cancelacion = null;
        $cita->id_procedimiento   = $plan ? $plan->id : $request->id_procedimiento;
        $cita->save();

        // Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Cita registrada exitosamente.',
            'data'    => $cita
        ], 201);
    }

    public function variasCitas(Request $request)
    {
        $plan = null;
        $citas = [];

        try {
            DB::beginTransaction();

        if ($request->procedimiento) {
            // Validar si ya existe el procedimiento para el paciente
            $plan = Plan_manejo_procedimiento::where('id_paciente', $request->Cita['id_paciente'])
                ->where('codigo', $request->Cita['codigo'])
                ->first();

            if ($plan) {
                // Si existe, sumar los días_asignados
                $plan->dias_asignados += 1;
                $plan->save();
            } else {
                // Si no existe, crear nuevo
                $plan = Plan_manejo_procedimiento::create([
                    'id_paciente'    => $request->Cita['id_paciente'],
                    'id_medico'      => $request->Cita['id_medico'],
                    'procedimiento'  => $request->Cita['procedimiento'],
                    'codigo'         => $request->Cita['codigo'],
                    'dias_asignados' => $request->Cita['dias_asignados'] ?? 1,
                ]);
            }
        }
        $cantidad = intval($request->Cita['cantidadCitas']) ?? 1;
        $intervalo = intval($request->Cita['intervaloCitas']) ?? 1;

        $fechaInicial = new \DateTime($request->Cita['fecha']);

        for ($i = 0; $i < $cantidad; $i++) {
            // Clonar la fecha inicial
            $fechaCita = clone $fechaInicial;
            // Sumar intervalo en días multiplicado por el índice
            $fechaCita->modify("+".($i * $intervalo)." days");

            $cita = new Cita();
            $cita->id_paciente        = $request->Cita['id_paciente'];
            $cita->id_medico          = $request->Cita['id_medico'];
            $cita->id_analisis        = null;
            $cita->id_servicio        = $request->Cita['id_servicio'];
            $cita->motivo             = $request->Cita['motivo'];
            $cita->fecha              = $fechaCita->format('Y-m-d');
            $cita->fechaHasta         = $request->Cita['fechaHasta'];
            $cita->hora               = $request->Cita['hora'] ?? '00:00:00';
            $cita->estado             = 'Inactiva';
            $cita->motivo_cancelacion = null;
            $cita->id_procedimiento   = $plan ? $plan->id : $request->Cita['id_procedimiento'];
            $cita->save();

            $citas[] = $cita;
        }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar las citas: ' . $e->getMessage(),
            ], 500);
        }
        DB::commit();

        // Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Citas registradas exitosamente.',
            'data'    => $citas
        ], 201);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function show(Cita $cita)
    {
        return $cita;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cita $cita)
    {
        $cita = Cita::where('id', $request->id)->first();

        if(!$cita){
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ]);
        }

        $cita->id_servicio = $request->id_servicio;
        $cita->motivo = $request->motivo;
        $cita->fecha = $request->fecha;
        $cita->fechaHasta = $request->fechaHasta;
        $cita->hora = $request->hora;
        $cita->motivo_edicion = $request->motivo_edicion;
        $cita->save();

        // Respuesta 
        return response()->json([
            'success' => true,
            'message' => 'Cita actualizada exitosamente.',
            'data' => $cita
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Cita $cita)
    {
        $cita = Cita::where('id', $request->id)->first();
        if($cita){
            $cita->estado = $request->estado;
            $cita->motivo_cancelacion = $request->motivo_cancelacion;
            $cita->save();
        }

        // Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Cita cancelada exitosamente.',
            'data' => $cita
        ], 200);
    }
}
