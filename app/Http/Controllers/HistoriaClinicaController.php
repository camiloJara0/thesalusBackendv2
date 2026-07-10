<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Clegginabox\PDFMerger\PDFMerger;
use Mpdf\Mpdf;
use Carbon\Carbon;
use App\Models\Historia_Clinica;
use App\Models\Analisis;
use App\Models\Diagnostico;
use App\Models\Diagnostico_relacionado;
use App\Models\Antecedente;
use App\Models\Enfermedad;
use App\Models\Examen_fisico;
use App\Models\Plan_manejo_medicamento;
use App\Models\Plan_manejo_procedimiento;
use App\Models\Plan_manejo_insumo;
use App\Models\Plan_manejo_equipo;
use App\Models\Cita;
use App\Models\Terapia;
use App\Models\Nota;
use App\Models\Descripcion_nota;
use App\Models\Movimiento;
use App\Models\Insumo;
use App\Models\Paciente;
use App\Models\Profesional;

use Illuminate\Http\Request;

class HistoriaClinicaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $pacientes = Paciente::with('infoUsuario')
            ->withExists('historiaClinica')
            ->get();

        $data = $pacientes->map(function ($paciente) {
            return [
                'id' => $paciente->id,
                'paciente' => $paciente->infoUsuario->name ?? '',
                'cedula' => $paciente->infoUsuario->No_document ?? '',
                'estado' => $paciente->historia_clinica_exists ? 'Creada' : 'Nueva',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function pacientesConHistoria(Request $request)
    {

        $pacientes = Paciente::with(['infoUsuario'])
            ->where('estado', 1)
            ->whereHas('citas', function ($query) use ($request) {
                $query->where('id_medico', $request->id_profesional);
            })
            ->withExists('historiaClinica')
            ->get();

        $data = $pacientes->map(function ($paciente) {
            return [
                'id' => $paciente->id,
                'paciente' => $paciente->infoUsuario->name ?? '',
                'cedula' => $paciente->infoUsuario->No_document ?? '',
                'estado' => $paciente->historia_clinica_exists ? 'Creada' : 'Nueva',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function traeDatosHistoria()
    {
        $analisis = Analisis::with('servicio', 'historia', 'diagnosticos', 'enfermedad', 'examenFisico', 'medicamentos', 'procedimientos', 'nota.descripcionNota', 'terapia', 'profesional.infoUsuario')->get();

        return response()->json([
            'success' => true,
            'data' => $analisis,
        ]);
    }

    public function analisisInicial()
    {
        $analisis = Analisis::with(
            'servicio',
            'historia',
            'diagnosticos',
            'enfermedad',
            'examenFisico',
            'medicamentos',
            'procedimientos',
            'nota.descripcionNota',
            'terapia',
            'profesional.infoUsuario'
        )
        ->orderBy('id', 'desc')
        ->limit(200)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $analisis
        ]);
    }

    public function analisisPaciente(Request $request)
    {
        $servicios = ['Nota', 'Medicina', 'Terapia', 'Evolucion', 'Trabajo Social'];

        $data = [];

        foreach ($servicios as $servicio) {
            $data[] = Analisis::with(
                'servicio',
                'historia',
                'diagnosticos',
                'enfermedad',
                'examenFisico',
                'medicamentos',
                'procedimientos',
                'nota.descripcionNota',
                'terapia',
                'profesional.infoUsuario'
            )
            ->whereHas('servicio', function ($q) use ($servicio) {
                $q->where('plantilla', $servicio);
            })
            ->whereHas('historia', function ($q) use ($request) {
                $q->where('id_paciente', $request->id);
            })
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function analisisPaginado(Request $request)
    {
        $ultimoId  = $request->input('ultimo_id', 0);
        $limite    = $request->input('limite', 20);
        $servicio  = $request->input('servicio');
        $paciente  = $request->input('paciente_id');

        $query = Analisis::with(
            'servicio',
            'historia',
            'diagnosticos',
            'enfermedad',
            'examenFisico',
            'medicamentos',
            'procedimientos',
            'nota.descripcionNota',
            'terapia',
            'profesional.infoUsuario'
        );

        if ($servicio) {
            $query->whereHas('servicio', function ($q) use ($servicio) {
                $q->where('plantilla', $servicio);
            });
        }

        if ($paciente) {
            $query->whereHas('historia', function ($q) use ($paciente) {
                $q->where('id_paciente', $paciente);
            });
        }

        if ($ultimoId > 0) {
            $query->where('id', '<', $ultimoId);
        }

        $data = $query
            ->orderBy('id', 'desc')
            ->limit($limite)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function analisisFiltrado(Request $request)
    {
        $query = Analisis::with(
            'servicio',
            'historia',
            'diagnosticos',
            'enfermedad',
            'examenFisico',
            'medicamentos',
            'procedimientos',
            'nota.descripcionNota',
            'terapia',
            'profesional.infoUsuario'
        );

        if ($request->filled('paciente_id')) {
            $query->whereHas('historia', function ($q) use ($request) {
                $q->where('id_paciente', $request->paciente_id);
            });
        }

        if ($request->filled('servicio')) {
            $query->whereHas('servicio', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->servicio}%");
            });
        }

        if ($request->filled('plantilla')) {
            $query->whereHas('servicio', function ($q) use ($request) {
                $q->where('plantilla', 'like', "%{$request->plantilla}%");
            });
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                $request->fecha_inicio,
                $request->fecha_fin
            ]);
        }

        if ($request->filled('mes')) {
            $query->whereMonth('created_at', $request->mes);
        }

        if ($request->filled('fecha_año')) {
            $query->whereYear('created_at', $request->anio);
        }

        if ($request->filled('profesional')) {
            $query->whereHas('profesional.infoUsuario', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->profesional}%");
            });
        }

        $data = $query
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function filtrosAnalisis()
    {
        $años = DB::table('analises')
            ->select(DB::raw('YEAR(created_at) as año'))
            ->distinct()
            ->pluck('año');

        $profesionales = DB::table('profesionals')
            ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
            ->select('informacion_users.name')
            ->distinct()
            ->pluck('name');

        return response()->json([
            'success' => true,
            'data' => [
                'años' => $años,
                'profesionales' => $profesionales
            ]
        ]);
    }

    public function traeDatosDiagnosticos()
    {
        $diagnosticos = Diagnostico::get();
        $diagnosticosRelacionados = Diagnostico_relacionado::get();

        return response()->json([
            'success' => true,
            'data' => $historia
        ]); 
    }

    public function traeDatosPlanManejo()
    {
        $medicamentos = Plan_manejo_medicamento::get();
        $procedimientos = Plan_manejo_procedimiento::get();
        $insumos = Plan_manejo_insumo::get();
        $equipos = Plan_manejo_equipo::get();


        return response()->json([
            'success' => true,
            'medicamentos' => $medicamentos,
            'procedimientos' => $procedimientos,
            'insumos' => $insumos,
            'equipos' => $equipos,
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
        DB::beginTransaction();

        try {
            $data = $request->all();
            $ids = [];

            $historia = Historia_Clinica::where('id_paciente', $request->historia['id_paciente'])->first();
            
            // 1️⃣ Guardar Historia Clínica
            if(!$historia){
                $historia = Historia_Clinica::create($data['historia']);
            }
            $ids['HistoriaClinica'] = $historia->id;

            // 2️⃣ Guardar Análisis con id_historia
            $data['Analisis']['id_historia'] = $historia->id;
            $analisis = Analisis::create([
                'motivo' => $data['motivo'],
                'observacion' => $data['observacion'],
                'tratamiento' => $data['tratamiento'],
                'analisis' => $data['analisis'],
                'tipoAnalisis' => $data['tipoAnalisis'],
                'id_historia' => $historia->id,
                'id_medico' => $data['id_medico'],
                'id_servicio' => $data['id_servicio'],
            ]);
            $ids['Analisis'] = $analisis->id;

            $ids['Diagnosticos'] = [];
            foreach ($data['Diagnosticos'] ?? [] as $diagnostico) {
                $nuevo = Diagnostico::create(array_merge((array)$diagnostico, [
                    'id_analisis' => $analisis->id
                ]));
                $ids['Diagnosticos'][] = $nuevo->id;
            }

            $ids['Antecedentes'] = [];
            foreach ($data['Antecedentes'] ?? [] as $antecedente) {
                $nuevo = Antecedente::create(array_merge((array)$antecedente, [
                    'id_paciente' => $historia->id_paciente
                ]));
                $ids['Antecedentes'][] = $nuevo->id;
            }


            if (!empty($data['Enfermedad'])) {
                $enfermedad = Enfermedad::create([
                    ...$data['Enfermedad'],
                    'id_analisis' => $analisis->id,
                    'id_paciente' => $historia->id_paciente,
                    'fecha_diagnostico' => now(),
                ]);
                $ids['Enfermedad'] = $enfermedad->id;
            }

            if (!empty($data['ExamenFisico'])) {
                $examen = $data['ExamenFisico'];
                $signos = $examen['signosVitales'] ?? [];

                $examenFisico = Examen_fisico::create([
                    'peso' => $examen['peso'],
                    'altura' => $examen['altura'],
                    'otros' => $examen['otros'],
                    'id_analisis' => $analisis->id,
                    'signosVitales' => $signos
                ]);

                $ids['ExamenFisico'] = $examenFisico->id;
            }

            foreach (['Plan_manejo_medicamentos' => Plan_manejo_medicamento::class,
                      'Plan_manejo_insumos' => Plan_manejo_insumo::class,
                      'Plan_manejo_equipos' => Plan_manejo_equipo::class] as $key => $model) {
                if (!empty($data[$key])) {
                    $ids[$key] = [];
                    foreach ($data[$key] as $item) {
                        $nuevo = $model::create([
                            ...$item,
                            'id_analisis' => $analisis->id,
                        ]);
                        $ids[$key][] = $nuevo->id;

                    }
                }
            }


            if (!empty($data['Plan_manejo_procedimientos'])) {
                $ids['Plan_manejo_procedimientos'] = [];
                    foreach ($data['Plan_manejo_procedimientos'] as $item) {
                        $nuevo = Plan_manejo_procedimiento::create([
                            ...$item,
                            'id_analisis' => $analisis->id,
                        ]);
                        $ids['Plan_manejo_procedimientos'][] = $nuevo->id;
                    }
                }

            // 4️⃣ Actualizar estado de la Cita
            Cita::where('id', $data['Cita']['id'] ?? null)
                ->update([
                    'estado' => 'Realizada',
                    'id_analisis' => $analisis->id
                ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'ids' => $ids, 
                'Historia:' => $historia,
                'Analisis' => $analisis
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar historia clínica', 'message' => $e->getMessage()], 500);
        }

    }

    public function storeNutricion(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
            $ids = [];

            $historia = Historia_Clinica::where('id_paciente', $request->historia['id_paciente'])->first();
            
            // 1️⃣ Guardar Historia Clínica
            if(!$historia){
                $historia = Historia_Clinica::create($data['historia']);
            }
            $ids['HistoriaClinica'] = $historia->id;

            // 2️⃣ Guardar Análisis con id_historia
            $data['Analisis']['id_historia'] = $historia->id;
            $analisis = Analisis::create([
                'motivo' => $data['motivo'],
                'observacion' => $data['observacion'],
                'tratamiento' => $data['tratamiento'],
                'analisis' => $data['analisis'],
                'tipoAnalisis' => $data['tipoAnalisis'],
                'id_historia' => $historia->id,
                'id_medico' => $data['id_medico'],
                'id_servicio' => $data['id_servicio'],
            ]);
            $ids['Analisis'] = $analisis->id;

            $ids['Diagnosticos'] = [];
            foreach ($data['Diagnosticos'] ?? [] as $diagnostico) {
                $nuevo = Diagnostico::create([...$diagnostico, 'id_analisis' => $analisis->id]);
                $ids['Diagnosticos'][] = $nuevo->id;
            }

            if (!empty($data['ExamenFisico'])) {
                $examen = $data['ExamenFisico'];
                $signos = $examen['signosVitales'] ?? [];

                $examenFisico = Examen_fisico::create([
                    'peso' => $examen['peso'],
                    'altura' => $examen['altura'],
                    'otros' => $examen['otros'],
                    'id_analisis' => $analisis->id,
                    'signosVitales' => $signos
                ]);

                $ids['ExamenFisico'] = $examenFisico->id;
            }

            foreach (['Plan_manejo_medicamentos' => Plan_manejo_medicamento::class] as $key => $model) {
                if (!empty($data[$key])) {
                    $ids[$key] = [];
                    foreach ($data[$key] as $item) {
                        $nuevo = $model::create([
                            ...$item,
                            'id_analisis' => $analisis->id,
                        ]);
                        $ids[$key][] = $nuevo->id;

                    }
                }
            }

            // 4️⃣ Actualizar estado de la Cita
            Cita::where('id', $data['Cita']['id'] ?? null)
                ->update([
                    'estado' => 'Realizada',
                    'id_analisis' => $analisis->id
                ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'ids' => $ids, 
                'Historia:' => $historia,
                'Analisis' => $analisis
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar historia clínica', 'message' => $e->getMessage()], 500);
        }

    }

    public function storeTrabajoSocial(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
            $ids = [];

            $historia = Historia_Clinica::where('id_paciente', $request->historia['id_paciente'])->first();
            
            // 1️⃣ Guardar Historia Clínica
            if(!$historia){
                $historia = Historia_Clinica::create($data['historia']);
            }
            $ids['HistoriaClinica'] = $historia->id;

            // 2️⃣ Guardar Análisis con id_historia
            $data['Analisis']['id_historia'] = $historia->id;
            $analisis = Analisis::create([
                'motivo' => $data['motivo'],
                'observacion' => $data['observacion'],
                'tratamiento' => $data['tratamiento'],
                'analisis' => $data['analisis'],
                'tipoAnalisis' => $data['tipoAnalisis'],
                'id_historia' => $historia->id,
                'id_medico' => $data['id_medico'],
                'id_servicio' => $data['id_servicio'],
            ]);
            $ids['Analisis'] = $analisis->id;

            $ids['Diagnosticos'] = [];
            foreach ($data['Diagnosticos'] ?? [] as $diagnostico) {
                $nuevo = Diagnostico::create([...$diagnostico, 'id_analisis' => $analisis->id]);
                $ids['Diagnosticos'][] = $nuevo->id;
            }

            foreach (['Plan_manejo_medicamentos' => Plan_manejo_medicamento::class,
                      'Plan_manejo_insumos' => Plan_manejo_insumo::class,
                      'Plan_manejo_equipos' => Plan_manejo_equipo::class] as $key => $model) {
                if (!empty($data[$key])) {
                    $ids[$key] = [];
                    foreach ($data[$key] as $item) {
                        $nuevo = $model::create([
                            ...$item,
                            'id_analisis' => $analisis->id,
                        ]);
                        $ids[$key][] = $nuevo->id;

                    }
                }
            }

            // 4️⃣ Actualizar estado de la Cita
            Cita::where('id', $data['Cita']['id'] ?? null)
                ->update([
                    'estado' => 'Realizada',
                    'id_analisis' => $analisis->id
                ]);


            DB::commit();

            return response()->json([
                'success' => true, 
                'ids' => $ids, 
                'Historia:' => $historia,
                'Analisis' => $analisis
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar historia clínica', 'message' => $e->getMessage()], 500);
        }

    }

    public function storeNota(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
            $ids = [];

            $historia = Historia_Clinica::where('id_paciente', $request->historia['id_paciente'])->first();
            
            // 1️⃣ Guardar Historia Clínica
            if(!$historia){
                $historia = Historia_Clinica::create($data['historia']);
            }
            $ids['HistoriaClinica'] = $historia->id;

            // 2️⃣ Guardar Análisis con id_historia
            $data['Analisis']['id_historia'] = $historia->id;
            $analisis = Analisis::create([
                'motivo' => 'Nota Medica',
                'observacion' => null,
                'tratamiento' => null,
                'analisis' => null,
                'tipoAnalisis' => $data['tipoAnalisis'],
                'id_historia' => $historia->id,
                'id_medico' => $data['id_medico'],
                'id_servicio' => $data['id_servicio'],
            ]);
            $ids['Analisis'] = $analisis->id;

            $ids['Diagnosticos'] = [];
            foreach ($data['Diagnosticos'] ?? [] as $diagnostico) {
                $nuevo = Diagnostico::create([...$diagnostico, 'id_analisis' => $analisis->id]);
                $ids['Diagnosticos'][] = $nuevo->id;
            }
            // Crear la nueva nota
            $nota = new Nota();
            $nota->direccion = $request->Nota['direccion'];
            $nota->fecha_nota = $request->Nota['fecha_nota'];
            $nota->hora_nota = $request->Nota['hora_nota'];
            $nota->tipoAnalisis = $request->Nota['tipoAnalisis'];
            $nota->id_analisis = $analisis->id;
            $nota->save();

            $ids['Descripcion'] = [];
            foreach ($data['Descripcion'] ?? [] as $descripcion) {
                $nuevo = Descripcion_nota::create([...$descripcion, 'id_nota' => $nota->id]);
                $ids['Descripcion'][] = $nuevo->id;
            }

            // 4️⃣ Actualizar estado de la Cita

            Cita::where('id', $data['Cita']['id'] ?? null)
                ->update([
                    'estado' => 'Realizada',
                    'id_analisis' => $analisis->id
                ]);


            DB::commit();

            return response()->json([
                'success' => true, 
                'ids' => $ids,
                'data' => $nota,
                'Historia:' => $historia
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar historia clínica', 'message' => $e->getMessage()], 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Historia_Clinica  $historia_Clinica
     * @return \Illuminate\Http\Response
     */
    public function show(Historia_Clinica $historia_Clinica)
    {
        return $historia_Clinica;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Historia_Clinica  $historia_Clinica
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Historia_Clinica $historia_Clinica)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
            $ids = [];

            // 1️⃣ Historia Clínica
            $historia = Historia_Clinica::firstOrCreate(
                ['id_paciente' => $data['HistoriaClinica']['id_paciente']],
                $data['HistoriaClinica']
            );
            $ids['HistoriaClinica'] = $historia->id;

            // 2️⃣ Análisis
            $data['Analisis']['id_historia'] = $historia->id;
            $analisis = Analisis::updateOrCreate(
                ['id' => $data['Analisis']['id'] ?? null],
                $data['Analisis']
            );
            $ids['Analisis'] = $analisis->id;

            // 3️⃣ Diagnósticos
            if (!empty($data['Diagnosticos'])) {
                $ids['Diagnosticos'] = [];
                foreach ($data['Diagnosticos'] as $diagnostico) {
                    $nuevo = Diagnostico::updateOrCreate(
                        ['id' => $diagnostico['id'] ?? null],
                        [...$diagnostico, 'id_analisis' => $analisis->id]
                    );
                    $ids['Diagnosticos'][] = $nuevo->id;
                }
            }

            // 4️⃣ Antecedentes
            if (!empty($data['Antecedentes'])) {
                $ids['Antecedentes'] = [];
                foreach ($data['Antecedentes'] as $antecedente) {
                    $nuevo = Antecedente::updateOrCreate(
                        ['id' => $antecedente['id'] ?? null],
                        $antecedente
                    );
                    $ids['Antecedentes'][] = $nuevo->id;
                }
            }

            // 5️⃣ Enfermedad
            if (!empty($data['Enfermedad'])) {
                $enfermedad = Enfermedad::updateOrCreate(
                    ['id' => $data['Enfermedad']['id'] ?? null],
                    [...$data['Enfermedad'], 'id_analisis' => $analisis->id]
                );
                $ids['Enfermedad'] = $enfermedad->id;
            }

            // 6️⃣ Examen físico
            if (!empty($data['ExamenFisico'])) {
                $examen = $data['ExamenFisico'];
                $signos = $examen['signosVitales'] ?? [];

                $examenFisico = Examen_fisico::updateOrCreate(
                    ['id' => $examen['id'] ?? null],
                    [
                        'Peso' => $examen['Peso'],
                        'altura' => $examen['altura'],
                        'otros' => $examen['otros'],
                        'id_analisis' => $analisis->id,
                        'signosVitales' => $signos
                    ]
                );
                $ids['ExamenFisico'] = $examenFisico->id;
            }

            // 7️⃣ Planes de manejo
            foreach ([
                'Plan_manejo_medicamentos' => Plan_manejo_medicamento::class,
                'Plan_manejo_insumos' => Plan_manejo_insumo::class,
                'Plan_manejo_equipos' => Plan_manejo_equipo::class
            ] as $key => $model) {
                if (!empty($data[$key])) {
                    $ids[$key] = [];
                    foreach ($data[$key] as $item) {
                        $nuevo = $model::updateOrCreate(
                            ['id' => $item['id'] ?? null],
                            [...$item, 'id_analisis' => $analisis->id]
                        );
                        $ids[$key][] = $nuevo->id;

                        // creación de Movimiento
                        // if ($key === 'Plan_manejo_insumos') {
                        //     Movimiento::create([
                        //         'cantidadMovimiento' => $item['cantidad'] ?? 0,
                        //         'fechaMovimiento'    => now(),
                        //         'tipoMovimiento'     => 'Engreso',
                        //         'id_medico'          => $data['Analisis']['id_medico'] ?? null,
                        //         'id_insumo'          => $item['id_insumo'],
                        //     ]);

                        //     // Actualizar stock del insumo
                        //     $insumo = Insumo::findOrFail($item['id_insumo']);
                        //     $insumo->stock -= $item['cantidad'] ?? 0;
                        //     $insumo->save();
                        // }

                    }
                }
            }



            // 8️⃣ Cita
            if (!empty($data['Cita'])) {
                Cita::where('id', $data['Cita']['id'] ?? null)
                    ->update([
                        'estado' => 'Realizada',
                        'id_analisis' => $analisis->id
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'ids' => $ids,
                'Historia' => $historia
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar/actualizar historia clínica',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Historia_Clinica  $historia_Clinica
     * @return \Illuminate\Http\Response
     */
    public function destroy(Historia_Clinica $historia_Clinica)
    {
        //
    }

    public function imprimirEvolucion($id)
    {
        $analisis = Analisis::with('servicio')->find($id);

        $historia = Historia_Clinica::where('id', $analisis->id_historia)->first();

        // Paciente con su información de usuario
        $paciente = DB::table('pacientes')
            ->join('informacion_users', 'pacientes.id_infoUsuario', '=', 'informacion_users.id')
            ->join('eps', 'pacientes.id_eps', '=', 'eps.id')
            ->where('pacientes.id', $historia->id_paciente)
            ->select('pacientes.*', 'informacion_users.*', 'eps.nombre as Eps')
            ->first();

        // Profesional con su información de usuario
        $profesional = DB::table('profesionals')
            ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
            ->where('profesionals.id', $analisis->id_medico)
            ->select('profesionals.*', 'informacion_users.*')
            ->first();

        // Diagnósticos que coincidan con el id_analisis
        $diagnosticos = DB::table('diagnosticos')
            ->where('id_analisis', $analisis->id)
            ->get();
        
        $medicamentos = DB::table('plan_manejo_medicamentos')
            ->where('id_analisis', $analisis->id)
            ->get();

        $convenios = DB::table('paciente_has_convenios')
            ->where('id_paciente', $historia->id_paciente)
            ->join('convenios', 'paciente_has_convenios.id_convenio', '=', 'convenios.id')
            ->select('convenios.*')
            ->first();
            
        $pdfs = [];

        // PDF principal
        $pdfEvolucion = Pdf::loadView('pdf.evolucion', compact(
            'paciente','profesional','diagnosticos','analisis','medicamentos', 'convenios'
        ))->output();
        $tmpEvolucion = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmpEvolucion, $pdfEvolucion);
        $pdfs[] = $tmpEvolucion;

        // Si hay medicamentos
        if ($medicamentos->count() > 0) {
            $pdfFormula = Pdf::loadView('pdf.formulaMedica', compact('paciente','profesional','medicamentos','analisis', 'convenios'))->output();
            $tmpFormula = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($tmpFormula, $pdfFormula);
            $pdfs[] = $tmpFormula;
        }

        // Unir PDFs
        $pdfMerger = new PDFMerger;
        foreach ($pdfs as $pdfFile) {
            $pdfMerger->addPDF($pdfFile, 'all');
        }

        $fileName = 'Evolucion_' . $profesional->name . '_' . $analisis->created_at . '.pdf';

        return response($pdfMerger->merge('string'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function imprimirTrabajoSocial($id)
    {
        $analisis = Analisis::with('servicio')->find($id);

        $historia = Historia_Clinica::where('id', $analisis->id_historia)->first();

        // Paciente con su información de usuario
        $paciente = DB::table('pacientes')
            ->join('informacion_users', 'pacientes.id_infoUsuario', '=', 'informacion_users.id')
            ->join('eps', 'pacientes.id_eps', '=', 'eps.id')
            ->where('pacientes.id', $historia->id_paciente)
            ->select('pacientes.*', 'informacion_users.*', 'eps.nombre as Eps')
            ->first();

        // Profesional con su información de usuario
        $profesional = DB::table('profesionals')
            ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
            ->where('profesionals.id', $analisis->id_medico)
            ->select('profesionals.*', 'informacion_users.*')
            ->first();

        // Diagnósticos que coincidan con el id_analisis
        $diagnosticos = DB::table('diagnosticos')
            ->where('id_analisis', $analisis->id)
            ->get();

        $medicamentos = DB::table('plan_manejo_medicamentos')
            ->where('id_analisis', $analisis->id)
            ->get();
        
        $convenios = DB::table('paciente_has_convenios')
            ->where('id_paciente', $historia->id_paciente)
            ->join('convenios', 'paciente_has_convenios.id_convenio', '=', 'convenios.id')
            ->select('convenios.*')
            ->first();

        $pdfs = [];

        // PDF principal
        $pdfTrabajoSocial = Pdf::loadView('pdf.trabajoSocial', compact(
            'paciente','profesional','diagnosticos','analisis','medicamentos', 'convenios'
        ))->output();
        $tmpTrabajoSocial = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmpTrabajoSocial, $pdfTrabajoSocial);
        $pdfs[] = $tmpTrabajoSocial;

        // Si hay medicamentos
        if ($medicamentos->count() > 0) {
            $pdfFormula = Pdf::loadView('pdf.formulaMedica', compact('paciente','profesional','medicamentos','analisis', 'convenios'))->output();
            $tmpFormula = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($tmpFormula, $pdfFormula);
            $pdfs[] = $tmpFormula;
        }

        // Unir PDFs
        $pdfMerger = new PDFMerger;
        foreach ($pdfs as $pdfFile) {
            $pdfMerger->addPDF($pdfFile, 'all');
        }

        $fileName = 'TrabajoSocial_' . $profesional->name . '_' . $analisis->created_at . '.pdf';

        return response($pdfMerger->merge('string'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function imprimirMedicina($id)
    {
        $analisis = Analisis::with('servicio')->find($id);

        $historia = Historia_Clinica::where('id', $analisis->id_historia)->first();

        // Paciente con su información de usuario
        $paciente = DB::table('pacientes')
            ->join('informacion_users', 'pacientes.id_infoUsuario', '=', 'informacion_users.id')
            ->join('eps', 'pacientes.id_eps', '=', 'eps.id')
            ->where('pacientes.id', $historia->id_paciente)
            ->select('pacientes.*', 'informacion_users.*', 'eps.nombre as Eps')
            ->first();

        $antecedentes = DB::table('antecedentes')
            ->where('id_paciente', $historia->id_paciente)
            ->get();

        // Profesional con su información de usuario
        $profesional = DB::table('profesionals')
            ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
            ->where('profesionals.id', $analisis->id_medico)
            ->select('profesionals.*', 'informacion_users.*')
            ->first();

        $examenFisico = DB::table('examen_fisicos')
            ->where('id_analisis', $analisis->id)
            ->first();

        $enfermedades = DB::table('enfermedads')
            ->where('id_analisis', $analisis->id)
            ->first();

        // Diagnósticos que coincidan con el id_analisis
        $diagnosticos = DB::table('diagnosticos')
            ->where('id_analisis', $analisis->id)
            ->get();

        $medicamentos = DB::table('plan_manejo_medicamentos')
            ->where('id_analisis', $analisis->id)
            ->get();

        $procedimientos = DB::table('plan_manejo_procedimientos')
            ->where('id_analisis', $analisis->id)
            ->get();

        $convenios = DB::table('paciente_has_convenios')
            ->where('id_paciente', $historia->id_paciente)
            ->join('convenios', 'paciente_has_convenios.id_convenio', '=', 'convenios.id')
            ->select('convenios.*')
            ->first();


        $pdfs = [];

        // PDF principal
        $pdfMedicina = Pdf::loadView('pdf.medicina', compact(
            'paciente','profesional','diagnosticos','analisis',
            'antecedentes','examenFisico','enfermedades','medicamentos','procedimientos', 'convenios'
        ))->output();
        $tmpMedicina = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmpMedicina, $pdfMedicina);
        $pdfs[] = $tmpMedicina;

        // Si hay medicamentos
        if ($medicamentos->count() > 0) {
            $pdfFormula = Pdf::loadView('pdf.formulaMedica', compact('paciente','profesional','medicamentos','analisis', 'convenios'))->output();
            $tmpFormula = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($tmpFormula, $pdfFormula);
            $pdfs[] = $tmpFormula;
        }

        // Si hay procedimientos
        if ($procedimientos->count() > 0) {
            $pdfProcedimientos = Pdf::loadView('pdf.planProcedimientos', compact('paciente','profesional','procedimientos', 'analisis', 'convenios'))->output();
            $tmpProcedimientos = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($tmpProcedimientos, $pdfProcedimientos);
            $pdfs[] = $tmpProcedimientos;
        }

        // Unir PDFs
        $pdfMerger = new PDFMerger;
        foreach ($pdfs as $pdfFile) {
            $pdfMerger->addPDF($pdfFile, 'all');
        }

        $fileName = 'Medicina_' . $profesional->name . '_' . $analisis->created_at . '.pdf';

        return response($pdfMerger->merge('string'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

    }


    public function exportarPdf(Request $request)
    {

    $fechaInicio = $request->fechaInicio;
    $fechaFin = $request->fechaFin;
    $idPaciente = $request->id_paciente;
    $idProfesional = $request->id_profesional;
    $servicio = $request->servicio;

    $query = Analisis::with(
        'servicio',
        'historia',
        'diagnosticos',
        'enfermedad',
        'examenFisico',
        'medicamentos',
        'procedimientos',
        'nota.descripcionNota',
        'terapia',
        'profesional.infoUsuario'
    );

    // Filtro por fecha
    $query->whereBetween('created_at', [
        Carbon::parse($fechaInicio)->startOfDay(),
        Carbon::parse($fechaFin)->endOfDay()
    ]);

    // Filtro por profesional
    if (!empty($idProfesional)) {
        $query->whereHas('profesional', function ($q) use ($idProfesional) {
            $q->where('id', $idProfesional);
        });
    }

    // Filtro por paciente
    if (!empty($idPaciente)) {
        $query->whereHas('historia', function ($q) use ($idPaciente) {
            $q->where('id_paciente', $idPaciente);
        });
    }

    // Filtro por servicio
    if (!empty($servicio)) {
        $query->whereHas('servicio', function ($q) use ($servicio) {
            $q->where('plantilla', $servicio);
        });
    }

    $analisisList = $query->get();

    if($request->excel){
        return response()->json([
            'success' => true,
            'data' => $analisisList
        ], 200);
    }
        
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4'
]);

$indiceNotas = [];

/*
|--------------------------------------------------------------------------
| Construcción índice
|--------------------------------------------------------------------------
*/

foreach ($analisisList as $analisis) {

    $indiceNotas[] = [
        'id' => $analisis->id,
        'titulo' => "Nota Médica #{$analisis->id}",
        'anchor' => "nota_{$analisis->id}"
    ];
}

/*
|--------------------------------------------------------------------------
| Índice
|--------------------------------------------------------------------------
*/

$mpdf->WriteHTML(
    view('pdf.indiceNotas', compact('indiceNotas'))->render()
);

/*
|--------------------------------------------------------------------------
| Notas
|--------------------------------------------------------------------------
*/

foreach ($analisisList as $analisis) {

    $historia = Historia_Clinica::find($analisis->id_historia);

    $id = $analisis->id;

    $paciente = DB::table('pacientes')
        ->join('informacion_users', 'pacientes.id_infoUsuario', '=', 'informacion_users.id')
        ->join('eps', 'pacientes.id_eps', '=', 'eps.id')
        ->where('pacientes.id', $historia->id_paciente)
        ->select(
            'pacientes.*',
            'informacion_users.*',
            'eps.nombre as Eps'
        )
        ->first();

    $profesional = DB::table('profesionals')
        ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
        ->where('profesionals.id', $analisis->id_medico)
        ->select(
            'profesionals.*',
            'informacion_users.*'
        )
        ->first();

    $diagnosticos = DB::table('diagnosticos')
        ->where('id_analisis', $analisis->id)
        ->get();

    $medicamentos = DB::table('plan_manejo_medicamentos')
        ->where('id_analisis', $analisis->id)
        ->get();

    $procedimientos = DB::table('plan_manejo_procedimientos')
        ->where('id_analisis', $analisis->id)
        ->get();

    $antecedentes = DB::table('antecedentes')
        ->where('id_paciente', $historia->id_paciente)
        ->get();

    $examenFisico = DB::table('examen_fisicos')
        ->where('id_analisis', $analisis->id)
        ->first();

    $enfermedades = DB::table('enfermedads')
        ->where('id_analisis', $analisis->id)
        ->first();

    $convenios = DB::table('paciente_has_convenios')
        ->where('id_paciente', $historia->id_paciente)
        ->join(
            'convenios',
            'paciente_has_convenios.id_convenio',
            '=',
            'convenios.id'
        )
        ->select('convenios.*')
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Datos header
    |--------------------------------------------------------------------------
    */

    $headerHtml = view('pdf.IndiceHeader', [
        'servicio' => $analisis->servicio->name ?? $request->servicio,
        'id'       => $analisis->id,
        'fecha'    => \Carbon\Carbon::parse(
            $analisis->created_at
        )->format('Y/m/d'),
        'nombre'   => $convenios->nombre ?? 'Santa Isabel IPS'
    ])->render();

    /*
    |--------------------------------------------------------------------------
    | Header para esta nota
    |--------------------------------------------------------------------------
    */

    $mpdf->SetHTMLHeader($headerHtml);

    /*
    |--------------------------------------------------------------------------
    | Selección de vista
    |--------------------------------------------------------------------------
    */

    switch ($request->servicio) {

        case 'Evolucion':

            $view = 'pdf.IndiceEvolucion';
            $data = compact(
                'paciente',
                'profesional',
                'diagnosticos',
                'analisis',
                'medicamentos',
                'convenios'
            );

            break;

        case 'Trabajo Social':

            $view = 'pdf.IndiceTrabajoSocial';
            $data = compact(
                'paciente',
                'profesional',
                'diagnosticos',
                'analisis',
                'medicamentos',
                'convenios'
            );

            break;

        case 'Medicina':

            $view = 'pdf.IndiceMedicina';

            $data = compact(
                'paciente',
                'profesional',
                'diagnosticos',
                'analisis',
                'antecedentes',
                'examenFisico',
                'medicamentos',
                'procedimientos',
                'convenios',
                'enfermedades'
            );

            break;

        case 'Nota':

            $nota = DB::table('notas')
                ->where('id_analisis', $analisis->id)
                ->first();

            $descripcion = DB::table('descripcion_nota')
                ->where('id_nota', $nota->id)
                ->get();

            $view = 'pdf.IndiceNota';

            $data = compact(
                'nota',
                'paciente',
                'profesional',
                'diagnosticos',
                'descripcion',
                'analisis',
                'convenios'
            );

            break;

        case 'Terapia':

            $terapia = DB::table('terapia')
                ->where('id_analisis', $analisis->id)
                ->first();

            $diagnosticosCIF = DB::table('diagnostico_relacionados')
                ->where('id_analisis', $terapia->id_analisis)
                ->get();

            $view = 'pdf.IndiceTerapia';

            $data = compact(
                'terapia',
                'paciente',
                'profesional',
                'diagnosticos',
                'diagnosticosCIF',
                'analisis',
                'convenios'
            );

            break;

        default:

            return response()->json([
                'error' => 'Servicio no válido'
            ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | Nueva página
    |--------------------------------------------------------------------------
    */

    $mpdf->AddPage();

    /*
    |--------------------------------------------------------------------------
    | Ancla para índice + contenido
    |--------------------------------------------------------------------------
    */

    $contenido = "<a name='nota_{$id}'></a>";
    $contenido .= view($view, $data)->render();

    $mpdf->WriteHTML($contenido);
}
        if($request->id_paciente){
            $paciente = Paciente::where('id', $request->id_paciente)->with('infoUsuario')->first();
            $fileName = strtoupper($request->servicio) . '_PACIENTE_' . $paciente->infoUsuario->name . '.pdf';
        } else if($request->id_profesional) {
            $paciente = Profesional::where('id', $request->id_profesional)->with('infoUsuario')->first();
            $fileName = strtoupper($request->servicio) . '_PROFESIONAL_' . $paciente->infoUsuario->name . '.pdf';
        } else {
            $fileName = strtoupper($request->servicio) . '_GENERAL.pdf';
        }

        return response($mpdf->Output($fileName, 'S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Content-Disposition', "attachment; filename=\"$fileName\"");
    }
}
