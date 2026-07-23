<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\Tipo_equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Illuminate\Support\Facades\Log;


class InsumoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $insumos = Insumo::with('infoMedicamento', 'infoInsumo', 'infoEquipo', 'movimientos.medico.infoUsuario')->where('estado', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $insumos
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
        $validated = $request->validate([
            'nombre' => 'required|unique:insumos,nombre',
            'categoria' => 'nullable|string|max:100',
            'stock' => 'integer|min:0',
            'es_prestable' => 'integer|max:1',
        ]);

        DB::beginTransaction();

        try {
            $validated['estado'] = 1;
            $insumo = Insumo::create($validated);

            if($request->categoria === 'Medicamento') {
                $insumo->infoMedicamento()->create([
                    'activo' => $request->activo,
                    'unidad' => $request->unidad,
                    'lote' => $request->lote,
                    'vencimiento' => $request->vencimiento,
                    'inventario_id' => $insumo->id,
                ]);
            } else if($request->categoria === 'Insumos médicos') {
                $insumo->infoInsumo()->create([
                    'unidad' => $request->unidad,
                    'especificaciones' => $request->especificaciones,
                    'lote' => $request->lote,
                    'vencimiento' => $request->vencimiento,
                    'ubicacion' => $request->ubicacion,
                    'inventario_id' => $insumo->id,
                ]);
            } else {
                $tipo_equipo = null;
                if($request->nombre_tipo){
                    $tipo_equipo = Tipo_equipo::firstOrCreate(['nombre' => $request->nombre_tipo, 'descripcion' => $request->descripcion_tipo]);
                }

                $insumo->infoEquipo()->create([
                    'serial' => $request->serial,
                    'tipo_equipo_id' => $request->tipo_equipo_id ?? $tipo_equipo->id,
                    'inventario_id' => $insumo->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Insumo creado correctamente',
                'data' => $insumo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar Insumo', ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error al registrar Insumo'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function show(Insumo $insumo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function edit(Insumo $insumo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Insumo $insumo)
    {
        $insumo = Insumo::findOrFail($request->id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'nullable|string|max:100',
            'stock' => 'integer|min:0',
            'es_prestable' => 'integer|max:1',
        ]);

        DB::beginTransaction();

        try {
            $insumo->update($validated);

            if($request->categoria === 'Medicamento') {
                $insumo->infoMedicamento()->update([
                    'activo' => $request->activo,
                    'unidad' => $request->unidad,
                    'lote' => $request->lote,
                    'vencimiento' => $request->vencimiento,
                    'inventario_id' => $insumo->id,
                ]);
            } else if($request->categoria === 'Insumos médicos') {
                $insumo->infoInsumo()->update([
                    'unidad' => $request->unidad,
                    'especificaciones' => $request->especificaciones,
                    'lote' => $request->lote,
                    'vencimiento' => $request->vencimiento,
                    'ubicacion' => $request->ubicacion,
                    'inventario_id' => $insumo->id,
                ]);
            } else {
                $tipo_equipo = null;
                if($request->nombre_tipo){
                    $tipo_equipo = Tipo_equipo::firstOrCreate(['nombre' => $request->nombre_tipo, 'descripcion' => $request->descripcion_tipo]);
                }
                $insumo->infoEquipo()->update([
                    'serial' => $request->serial,
                    'tipo_equipo_id' => $request->tipo_equipo_id ?? $tipo_equipo->id,
                    'inventario_id' => $insumo->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Insumo actualizado correctamente',
                'data' => $insumo
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar Insumo', ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar Insumo'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Insumo $insumo)
    {
        $insumo = Insumo::findOrFail($request->id);

        $insumo->estado = 0;
        $insumo-> save();

        return response()->json([
            'success' => true,
            'message' => 'Insumo actualizado correctamente',
            'data' => $insumo
        ], 200);
    }

    public function importar(Request $request)
    {
        $request->validate([
                'file' => 'required|file|mimes:csv,txt',
            ]);

            $file = fopen($request->file('file')->getRealPath(), 'r');

            // Leer encabezados
            $headers = fgetcsv($file);
            // limpiar BOM en la primera columna
            if (isset($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            }

            // normalizar todos los encabezados
            $headers = array_map(function($h) {
                return trim(mb_convert_encoding($h, 'UTF-8', 'auto'));
            }, $headers);


            $insertados = [];
            $errores = [];
            $fila = 1; // contador de filas (empezamos en 1 porque ya leímos encabezados)

            while (($row = fgetcsv($file)) !== false) {
                $fila++;

                // Normalizar valores (trim)
                $row = array_map(function($value) {
                    return trim(mb_convert_encoding($value, 'UTF-8', 'auto'));
                }, $row);

                // Saltar filas vacías
                if (count(array_filter($row)) === 0) {
                    $errores[] = [
                        'fila' => $fila,
                        'error' => 'Fila vacía'
                    ];
                    continue;
                }

                // Saltar filas con columnas incompletas
                if (count($row) !== count($headers)) {
                    $errores[] = [
                        'fila' => $fila,
                        'error' => 'Número de columnas incorrecto'
                    ];
                    continue;
                }

                $record = array_combine($headers, $row);

                // Normalizar valores
                $record['receta'] = strtolower(trim($record['receta'])) === 'si' ? true : false;

                if (!empty($record['vencimiento'])) {
                    try {
                        $valor = $record['vencimiento'];

                        if (is_numeric($valor)) {
                            $baseDate = \Carbon\Carbon::create(1900, 1, 1)->subDay(); 
                            $fecha = $baseDate->copy()->addDays($valor);
                        } else {
                            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $valor);
                        }

                        // Guardar en formato YYYY-MM-DD para MySQL
                        $record['vencimiento'] = $fecha->toDateString();

                    } catch (\Exception $e) {
                        $errores[] = [
                            'fila' => $fila,
                            'error' => 'Formato de fecha inválido'
                        ];
                        continue;
                    }
                }


                if (!empty($record['stock'])) {
                    $record['stock'] = (int) str_replace(',', '.', $record['stock']);
                }

                // Validar con reglas de Laravel
                $validator = validator($record, [
                    'nombre' => 'required|string|max:255',
                    'categoria' => 'nullable|string|max:100',
                    'activo' => 'nullable|string|max:100',
                    'receta' => 'boolean',
                    'unidad' => 'nullable|string|max:50',
                    'stock' => 'integer|min:0',
                    'lote' => 'nullable|string|max:50',
                    'vencimiento' => 'nullable|date',
                    'ubicacion' => 'nullable|string|max:100',
                    'es_prestable' => 'integer|max:1',
                ]);

                if ($validator->fails()) {
                    $errores[] = [
                        'fila' => $fila,
                        'error' => $validator->errors()->all()
                    ];
                    continue;
                }

                // Evitar duplicados
                if (Insumo::where('nombre', $record['nombre'])->exists()) {
                    $errores[] = [
                        'fila' => $fila,
                        'error' => 'Duplicado por nombre'
                    ];
                    continue;
                }

                $validated = $validator->validated();
                $validated['estado'] = 1;

                $insertados[] = Insumo::create($validated);
            }

            fclose($file);

            return response()->json([
                'success' => true,
                'message' => 'Importación completada',
                'insertados' => $insertados,
                'errores' => $errores
            ]);
    }


}
