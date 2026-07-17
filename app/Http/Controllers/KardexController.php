<?php

namespace App\Http\Controllers;

use App\Models\KardexCampo;
use App\Models\KardexPlantilla;
use App\Models\KardexPlantillaCampo;
use App\Models\KardexRegistro;
use App\Models\Historial_cambio_sonda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KardexController extends Controller
{
    public function plantillas()
    {
        $plantillas = KardexPlantilla::where('activo', true)->get();

        return response()->json([
            'success' => true,
            'data'    => $plantillas,
        ]);
    }

    public function plantillaCampos($id)
    {
        $plantilla = KardexPlantilla::with(['campos' => function ($query) {
            $query->where('kardex_campos.activo', true);
        }])->find($id);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $plantilla,
        ]);
    }

    public function registrosPaciente(Request $request, $idPaciente)
    {
        $idPlantilla = $request->query('plantilla');

        $query = KardexRegistro::where('kardex_registros.id_paciente', $idPaciente)
            ->join('kardex_campos', 'kardex_registros.id_campo', '=', 'kardex_campos.id')
            ->select(
                'kardex_registros.*',
                'kardex_campos.nombre',
                'kardex_campos.titulo',
                'kardex_campos.tipo',
                'kardex_campos.opciones'
            );

        if ($idPlantilla) {
            $query->whereIn('kardex_registros.id_campo', function ($sub) use ($idPlantilla) {
                $sub->select('id_campo')
                    ->from('kardex_plantilla_campos')
                    ->where('id_plantilla', $idPlantilla);
            });
        }

        $registros = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $registros,
        ]);
    }

    public function registrosPlantilla(Request $request)
    {
        $idPlantilla = $request->query('id_plantilla');

        $query = KardexRegistro::join('kardex_campos', 'kardex_registros.id_campo', '=', 'kardex_campos.id')
            ->select(
                'kardex_registros.*',
                'kardex_campos.nombre',
                'kardex_campos.titulo',
                'kardex_campos.tipo',
                'kardex_campos.opciones'
            );

        if ($idPlantilla) {
            $query->whereIn('kardex_registros.id_campo', function ($sub) use ($idPlantilla) {
                $sub->select('id_campo')
                    ->from('kardex_plantilla_campos')
                    ->where('id_plantilla', $idPlantilla);
            });
        }

        $registros = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $registros,
        ]);
    }

    public function guardarRegistros(Request $request)
    {
        $request->validate([
            'id_paciente'           => 'required|integer|exists:pacientes,id',
            'registros'             => 'required|array',
            'registros.*.id_campo'  => 'required|integer|exists:kardex_campos,id',
            'registros.*.valor'     => 'nullable|string',
        ]);

        $idPaciente = $request->id_paciente;

        DB::beginTransaction();

        try {
            foreach ($request->registros as $registro) {
                KardexRegistro::updateOrInsert(
                    ['id_paciente' => $idPaciente, 'id_campo' => $registro['id_campo']],
                    ['valor' => $registro['valor'], 'updated_at' => now()]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registros guardados exitosamente.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar registros: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeHistorialCambioSonda(Request $request)
    {
        $request->validate([
            'id_paciente'    => 'required|integer|exists:pacientes,id',
            'ultimoCambio'   => 'required|date',
            'tipo_sonda'     => 'nullable|string',
            'observacion'    => 'nullable|string',
        ]);

        $campoUltimoCambio = KardexCampo::where('nombre', 'ultimoCambio')->first();

        if ($campoUltimoCambio) {
            KardexRegistro::updateOrInsert(
                ['id_paciente' => $request->id_paciente, 'id_campo' => $campoUltimoCambio->id],
                ['valor' => $request->ultimoCambio, 'updated_at' => now()]
            );
        }

        $kardexRow = DB::table('kardex')->where('id_paciente', $request->id_paciente)->first();

        if ($kardexRow) {
            $historial = new Historial_cambio_sonda();
            $historial->id_kardex = $kardexRow->id;
            $historial->fecha_cambio = $request->ultimoCambio;
            $historial->tipo_sonda = $request->tipo_sonda;
            $historial->observacion = $request->observacion;
            $historial->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Historial de cambio de sonda registrado.',
        ]);
    }

    public function getCampos()
    {
        $campos = KardexCampo::where('activo', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $campos
        ]);
    }

    public function storeCampo(Request $request)
    {
        $request->validate([
            'nombre'        => 'required|string|max:255|unique:kardex_campos,nombre',
            'titulo'        => 'required|string|max:255',
            'tipo'          => 'required|in:text,boolean,select,date,number,textarea',
            'opciones'      => 'nullable|string',
            'descripcion'   => 'nullable|string',
            'valor_defecto' => 'nullable|string',
        ]);

        $campo = KardexCampo::create($request->only([
            'nombre', 'titulo', 'tipo', 'opciones', 'descripcion', 'valor_defecto',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Campo creado exitosamente.',
            'data'    => $campo,
        ], 201);
    }

    public function updateCampo(Request $request, $id)
    {
        $campo = KardexCampo::find($id);

        if (!$campo) {
            return response()->json([
                'success' => false,
                'message' => 'Campo no encontrado.',
            ], 404);
        }

        $request->validate([
            'titulo'        => 'sometimes|string|max:255',
            'tipo'          => 'sometimes|in:text,boolean,select,date,number,textarea',
            'opciones'      => 'nullable|string',
            'descripcion'   => 'nullable|string',
            'valor_defecto' => 'nullable|string',
            'activo'        => 'sometimes|boolean',
        ]);

        $campo->update($request->only([
            'titulo', 'tipo', 'opciones', 'descripcion', 'valor_defecto', 'activo',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Campo actualizado exitosamente.',
            'data'    => $campo,
        ]);
    }

    public function destroyCampo($id)
    {
        $campo = KardexCampo::find($id);

        if (!$campo) {
            return response()->json([
                'success' => false,
                'message' => 'Campo no encontrado.',
            ], 404);
        }

        $campo->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Campo desactivado exitosamente.',
        ]);
    }

    public function storePlantilla(Request $request)
    {
        $request->validate([
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'icono'        => 'nullable|string|max:255',
            'campos' => 'nullable|array'
        ]);

        $plantilla = KardexPlantilla::create($request->only([
            'nombre', 'descripcion', 'icono',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Plantilla creada exitosamente.',
            'data'    => $plantilla,
        ], 201);
    }

    public function updatePlantilla(Request $request, $id)
    {
        $plantilla = KardexPlantilla::find($id);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada.',
            ], 404);
        }

        $request->validate([
            'nombre'       => 'sometimes|string|max:255',
            'descripcion'  => 'nullable|string',
            'icono'        => 'nullable|string|max:255',
            'activo'       => 'sometimes|boolean',
            'campos' => 'nullable|array'
        ]);

        $plantilla->update($request->only([
            'nombre', 'descripcion', 'icono', 'activo',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Plantilla actualizada exitosamente.',
            'data'    => $plantilla,
        ]);
    }

    public function destroyPlantilla($id)
    {
        $plantilla = KardexPlantilla::find($id);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada.',
            ], 404);
        }

        $plantilla->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla desactivada exitosamente.',
        ]);
    }

    public function addCampoPlantilla(Request $request, $idPlantilla)
    {
        $plantilla = KardexPlantilla::find($idPlantilla);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada.',
            ], 404);
        }

        $request->validate([
            'id_campo'   => 'required|integer|exists:kardex_campos,id',
            'orden'      => 'nullable|integer',
            'requerido'  => 'nullable|boolean',
        ]);

        $pivot = KardexPlantillaCampo::updateOrCreate(
            ['id_plantilla' => $idPlantilla, 'id_campo' => $request->id_campo],
            [
                'orden'     => $request->orden ?? 0,
                'requerido' => $request->requerido ?? false,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Campo asignado a plantilla exitosamente.',
            'data'    => $pivot,
        ]);
    }

    public function removeCampoPlantilla($idPlantilla, $idCampo)
    {
        $deleted = KardexPlantillaCampo::where('id_plantilla', $idPlantilla)
            ->where('id_campo', $idCampo)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Relación no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campo removido de la plantilla exitosamente.',
        ]);
    }
}
