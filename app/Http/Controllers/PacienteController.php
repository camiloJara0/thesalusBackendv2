<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\InformacionUser;
use App\Models\Eps;
use App\Models\Plan_manejo_procedimiento;
use App\Models\Paciente_has_convenio;
use App\Models\Antecedente;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class PacienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paciente = Paciente::with('infoUsuario', 'eps', 'antecedente', 'convenios', 'planManejoProcedimientos')->where('estado', 1)->get();
        foreach ($paciente as $p) {
            $convenio = $p->convenios->first(); // devuelve el primer convenio o null
        }
        return response()->json([
            'success' => true,
            'data' => $paciente
        ], 201);
    }

    public function pacientesInactivos()
    {
        $paciente = Paciente::with('infoUsuario', 'eps', 'antecedente', 'convenios', 'planManejoProcedimientos')->where('estado', 0)->get();
        foreach ($paciente as $p) {
            $convenio = $p->convenios->first(); // devuelve el primer convenio o null
        }
        return response()->json([
            'success' => true,
            'data' => $paciente
        ], 201);
    }

    public function traePacientes(Request $request)
    {
        $pacientes = Paciente::with(['infoUsuario', 'eps', 'antecedente', 'convenios', 'planManejoProcedimientos'])
            ->where('estado', 1)
            ->whereHas('citas', function ($query) use ($request) {
                $query->where('id_medico', $request->id_profesional);
            })
            ->get();
        foreach ($pacientes as $p) {
            $convenio = $p->convenios->first(); // devuelve el primer convenio o null
        }
        return response()->json([
            'success' => true,
            'data' => $pacientes,
        ], 200);
    }

    public function traeKardex()
    {
        $pacientes = DB::table('pacientes')
            ->join('informacion_users', 'pacientes.id_infoUsuario', '=', 'informacion_users.id')
            ->join('eps', 'pacientes.id_eps', '=', 'eps.id')
            ->leftJoin('kardex', 'pacientes.id', '=', 'kardex.id_paciente')
            ->select(
                'pacientes.*',
                'informacion_users.*',
                'eps.nombre as Eps',
                'pacientes.id as paciente_id',
                'kardex.*'
            )
            ->get();

        
        $kardex = [];

        foreach ($pacientes as $paciente) {
            // id_historia
            $idHistoria = DB::table('historia__clinicas')
                ->where('id_paciente', $paciente->id_paciente)
                ->value('id');

            // Todos los análisis del paciente
            $analisisList = DB::table('analises')
                ->where('id_historia', $idHistoria)
                ->orderBy('created_at', 'asc')
                ->get();

            // Diagnósticos de todos los análisis
            $diagnosticos = DB::table('diagnosticos')
                ->whereIn('id_analisis', $analisisList->pluck('id'))
                ->pluck('descripcion');

            // Equipos de todos los análisis
            $equipos = DB::table('plan_manejo_equipos')
                ->whereIn('id_analisis', $analisisList->pluck('id'))
                ->pluck('descripcion');

            // $flagsEquipos = [
            //     'kit_cateterismo'   => $equipos->contains(fn($d) => str_contains(strtolower($d), 'cateterismo')) ? 'Si' : 'No',
            //     'kit_sonda'         => $equipos->contains(fn($d) => str_contains(strtolower($d), 'sonda')) ? 'Si' : 'No',
            //     'kit_gastro'        => $equipos->contains(fn($d) => str_contains(strtolower($d), 'gastro')) ? 'Si' : 'No',
            //     'traqueo'           => $equipos->contains(fn($d) => str_contains(strtolower($d), 'traqueo')) ? 'Si' : 'No',
            //     'oxigeno'           => $equipos->contains(fn($d) => str_contains(strtolower($d), 'oxigeno')) ? 'Si' : 'No',
            //     'vm'                => $equipos->contains(fn($d) => str_contains(strtolower($d), 'ventilador')) ? 'Si' : 'No',
            //     'equipos_biomedicos'=> $equipos->contains(fn($d) => str_contains(strtolower($d), 'equipos biomedicos')) ? 'Si' : 'No',
            // ];

            // Servicios de todos los análisis
            $serviciosAnalisis = DB::table('analises')
                ->join('servicio', 'analises.id_servicio', '=', 'servicio.id')
                ->join('informacion_users', 'analises.id_medico', '=', 'informacion_users.id')
                ->whereIn('analises.id', $analisisList->pluck('id'))
                ->select('servicio.name as servicio', 'informacion_users.name as medico', 'analises.created_at as created_at')
                ->get();

            // Buscar el último análisis con servicio de nutrición
            $ultimaNutricion = $serviciosAnalisis
                ->filter(fn($s) => str_contains(strtolower($s->servicio), 'nutricion'))
                ->last();

            // Si existe, formatear el mes; si no, devolver "N/A"
            $nutricionistaMes = $ultimaNutricion
                ? Carbon::parse($ultimaNutricion->created_at)->locale('es')->translatedFormat('F')
                : 'N/A';

            // Buscar el último análisis con servicio de nutrición
            $ultimaPsicologia = $serviciosAnalisis
                ->filter(fn($s) => str_contains(strtolower($s->servicio), 'psicologia'))
                ->last();

            // Si existe, formatear el mes; si no, devolver "N/A"
            $psicologiaMes = $ultimaPsicologia
                ? Carbon::parse($ultimaPsicologia->created_at)->locale('es')->translatedFormat('F')
                : 'N/A';

            $flagsServicios = [
                // Respiratoria
                'terapia_respiratoria' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'respiratoria'))
                    ->count(),
                'terapeuta_respiratoria' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'respiratoria'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',

                // Física
                'terapia_fisica' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'fisica'))
                    ->count(),
                'terapeuta_fisica' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'fisica'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',

                // Fonoaudiología
                'terapia_fonoaudiologia' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'fonoaudiologia'))
                    ->count(),
                'terapeuta_fonoaudiologia' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'fonoaudiologia'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',

                // Ocupacional
                'terapia_ocupacional' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'ocupacional'))
                    ->count(),
                'terapeuta_ocupacional' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'ocupacional'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',

                // Nutricionista
                'nutricionista' => $nutricionistaMes,
                'profesional_nutricionista' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'nutricion'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',

                // Psicología
                'psicologia' => $psicologiaMes,
                'profesional_psicologia' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'psicologia'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',

                // Trabajo social
                'trabajo_social' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'social'))
                    ->count(),
                'profesional_trabajo_social' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'social'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',

                // Guía espiritual
                'guia_espiritual' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'espiritual'))
                    ->count(),
                'profesional_guia_espiritual' => $serviciosAnalisis
                    ->filter(fn($s) => str_contains(strtolower($s->servicio), 'espiritual'))
                    ->pluck('medico')
                    ->unique()
                    ->implode(', ') ?: 'N/A',
            ];


            // Fecha última visita médica = último análisis
            $fechaUltimaVisita = optional($analisisList->last())->created_at;
            $pacienteArray = (array) $paciente;
            // Transformar los valores booleanos de kardex en "SI"/"NO"
            foreach ($pacienteArray as $key => $value) {
                if (is_bool($value)) {
                    $pacienteArray[$key] = $value ? 'SI' : 'NO';
                }
            }


            $kardex[] = [
                ...$pacienteArray,
                'diagnostico' => $diagnosticos->implode(', '),
                ...$flagsServicios,
                'fecha_ultima_visita' => Carbon::parse($fechaUltimaVisita)->locale('es')->translatedFormat('d F Y'),
            ];
        }


        return response()->json([
            'success' => true,
            'data' => $kardex,
        ], 201);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // 1️⃣ Buscar o crear el usuario
        $informacionUser = InformacionUser::where('No_document', $request->info_usuario['No_document'])->first();

        if($informacionUser){
            // 2️⃣ Guardar información adicional en InformacionUser
            return response()->json([
                'success' => true,
                'message' => 'Cedula ya registrada.',
            ], 500);
        }

        $informacionUser = InformacionUser::create([
            'name' => $request->info_usuario['name'],
            'No_document' => $request->info_usuario['No_document'],
            'type_doc' => $request->info_usuario['type_doc'],
            'celular' => $request->info_usuario['celular'],
            'telefono' => $request->info_usuario['telefono'],
            'nacimiento' => $request->info_usuario['nacimiento'],
            'direccion' => $request->info_usuario['direccion'],
            'municipio' => $request->info_usuario['municipio'],
            'departamento' => $request->info_usuario['departamento'],
            'barrio' => $request->info_usuario['barrio'],
            'zona' => $request->info_usuario['zona'],
        ]);

        // 3️⃣ Guardar datos del paciente
        $paciente = new Paciente();
        $paciente->id_infoUsuario = $informacionUser->id;
        $paciente->id_eps = $request->id_eps;
        $paciente->genero = $request->genero;
        $paciente->sexo = $request->sexo;
        $paciente->regimen = $request->regimen;
        $paciente->vulnerabilidad = $request->vulnerabilidad;
        $paciente->save();

        
        $idsEnviados = collect($data['plan_manejo_procedimientos'] ?? [])->pluck('id')->filter()->toArray();
        Plan_manejo_procedimiento::where('id_paciente', $paciente->id)
            ->whereNotIn('id', $idsEnviados)
            ->delete();
        foreach ($data['plan_manejo_procedimientos'] ?? [] as $item) {
            
            Plan_manejo_procedimiento::updateOrCreate(
                ['id' => $item['id'] ?? null], // si existe → busca
                [
                    ...$item,
                    'id_paciente' => $paciente->id
                ]
            );
        }

        $idsAntecedentes = collect($data['antecedente'] ?? [])->pluck('id')->filter()->toArray();
        Antecedente::where('id_paciente', $paciente->id)
            ->whereNotIn('id', $idsAntecedentes)
            ->delete();
        foreach ($data['antecedente'] ?? [] as $item) {
            Antecedente::updateOrCreate(
                ['id' => $item['id'] ?? null],
                [
                    ...$item,
                    'id_paciente' => $paciente->id
                ]
            );
        }

        if (!empty($request->convenio_id)) {

            DB::table('paciente_has_convenios')->insert([
                'id_paciente' => $paciente->id,
                'id_convenio' => $request->convenio_id
            ]);

        }

        // 4️⃣ Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Paciente registrado exitosamente.',
            'informacion' => $informacionUser,
            'paciente' => $paciente
        ], 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Paciente  $paciente
     * @return \Illuminate\Http\Response
     */
    public function show(Paciente $paciente)
    {
        return $paciente;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Paciente  $paciente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Paciente $paciente)
    {
        $data = $request->all();
        
        // Actualizar información del usuario
        $informacionUser = InformacionUser::where('No_document', $request->info_usuario['No_document'])->first();

        $informacionUser->update([
            'name' => $request->info_usuario['name'],
            'No_document' => $request->info_usuario['No_document'],
            'type_doc' => $request->info_usuario['type_doc'],
            'celular' => $request->info_usuario['celular'],
            'telefono' => $request->info_usuario['telefono'],
            'nacimiento' => $request->info_usuario['nacimiento'],
            'direccion' => $request->info_usuario['direccion'],
            'municipio' => $request->info_usuario['municipio'],
            'departamento' => $request->info_usuario['departamento'],
            'barrio' => $request->info_usuario['barrio'],
            'zona' => $request->info_usuario['zona'],
        ]);

        // 2️⃣ Actualizar datos del paciente
        $paciente = Paciente::where('id', $request->id)->first();


        $paciente->id_eps = $request->id_eps;
        $paciente->genero = $request->genero;
        $paciente->sexo = $request->sexo;
        $paciente->regimen = $request->regimen;
        $paciente->vulnerabilidad = $request->vulnerabilidad;
        $paciente->estado = $request->estado;
        $paciente->save();

        $idsEnviados = collect($data['plan_manejo_procedimientos'] ?? [])->pluck('id')->filter()->toArray();
        Plan_manejo_procedimiento::where('id_paciente', $paciente->id)
            ->whereNotIn('id', $idsEnviados)
            ->delete();
        foreach ($data['plan_manejo_procedimientos'] ?? [] as $item) {
            
            Plan_manejo_procedimiento::updateOrCreate(
                ['id' => $item['id'] ?? null], // si existe → busca
                [
                    ...$item,
                    'id_paciente' => $paciente->id
                ]
            );
        }

        $idsAntecedentes = collect($data['antecedente'] ?? [])->pluck('id')->filter()->toArray();
        Antecedente::where('id_paciente', $paciente->id)
            ->whereNotIn('id', $idsAntecedentes)
            ->delete();
        foreach ($data['antecedente'] ?? [] as $item) {
            Antecedente::updateOrCreate(
                ['id' => $item['id'] ?? null],
                [
                    ...$item,
                    'id_paciente' => $paciente->id
                ]
            );
        }

        if (!empty($request->convenio_id)) {
            if($request->convenio_id === 'Sin convenio') {
                DB::table('paciente_has_convenios')->where('id_paciente', $paciente->id)->delete();
            } else {
                $convenio = Paciente_has_convenio::where('id_paciente', $paciente->id)->first();
                if($convenio){
                    $convenio->id_convenio = $request->convenio_id;
                    $convenio->save();
                } else {
                    DB::table('paciente_has_convenios')->insert([
                        'id_paciente' => $paciente->id,
                        'id_convenio' => $request->convenio_id
                    ]);
                }
            }
        }

        // 3️⃣ Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Paciente actualizado exitosamente.',
            'informacion' => $informacionUser,
            'paciente' => $paciente
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Paciente  $paciente
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Paciente $paciente)
    {
        // Actualizar información del usuario
        $paciente = Paciente::find($request->id);

        if($paciente){
            $paciente->estado = 0;
            $paciente->save();

            // Cancelar citas inactivas del paciente
            Cita::where('id_paciente', $paciente->id)
                ->where('estado', 'Inactiva')
                ->update([
                    'estado' => 'cancelada',
                    'motivo_cancelacion' => 'Paciente eliminado',
                ]);
        }

        // Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Paciente deshabilitado exitosamente.',
            'data' => $paciente
        ], 200);
    }
}
