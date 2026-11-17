<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Jobs\ExportZipJob;
use App\Models\Export;
use App\Services\ExportConcurrencyService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrabajosController extends Controller
{
    public function index(Request $request)
    {
        $porCola = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as total'))
            ->groupBy('queue')
            ->get();

        $pendientes = (clone $porCola)->sum('total');
        $reservados = DB::table('jobs')->whereNotNull('reserved_at')->count();

        $fallidos = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(min((int) $request->input('por_pagina', 20), 100))
            ->get()
            ->map(function ($row) {
                $payload = json_decode($row->payload);

                return [
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'queue' => $row->queue,
                    'displayName' => $payload->displayName ?? null,
                    'exception' => Str::limit($row->exception, 500, '...'),
                    'failed_at' => $row->failed_at,
                ];
            });

        return response()->json([
            'success' => true,
            'cola' => [
                'pendientes' => (int) $pendientes,
                'reservados' => $reservados,
                'por_cola' => $porCola,
            ],
            'fallidos' => $fallidos,
        ]);
    }

    public function showFailed($id)
    {
        $fallido = DB::table('failed_jobs')->find($id);

        if (!$fallido) {
            return response()->json([
                'success' => false,
                'message' => 'Trabajo fallido no encontrado',
            ], 404);
        }

        $payload = json_decode($fallido->payload);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $fallido->id,
                'uuid' => $fallido->uuid,
                'queue' => $fallido->queue,
                'displayName' => $payload->displayName ?? null,
                'commandName' => $payload->data->commandName ?? null,
                'exception' => $fallido->exception,
                'failed_at' => $fallido->failed_at,
            ],
        ]);
    }

    public function retryFailed($id)
    {
        $fallido = DB::table('failed_jobs')->find($id);

        if (!$fallido) {
            return response()->json([
                'success' => false,
                'message' => 'Trabajo fallido no encontrado',
            ], 404);
        }

        $payload = json_decode($fallido->payload);
        $commandName = $payload->data->commandName ?? null;

        if ($commandName !== ExportZipJob::class) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede reintentar ExportZipJob',
            ], 400);
        }

        $job = @unserialize($payload->data->command);

        if (!$job instanceof ExportZipJob) {
            return response()->json([
                'success' => false,
                'message' => 'Payload invalido: no se pudo reconstruir el trabajo',
            ], 400);
        }

        if (!Export::find($job->exportId)) {
            return response()->json([
                'success' => false,
                'message' => 'La exportacion asociada ya no existe',
            ], 400);
        }

        ExportZipJob::dispatch($job->exportId);
        DB::table('failed_jobs')->where('id', $id)->delete();

        Audit::log('trabajos.reintento', [
            'modelo' => 'failed_jobs',
            'modelo_id' => $id,
            'descripcion' => "Reintento de ExportZipJob (export {$job->exportId})",
            'datos' => ['export_id' => $job->exportId, 'failed_job_id' => $id],
        ]);

        return response()->json([
            'success' => true,
            'export_id' => $job->exportId,
        ], 202);
    }

    public function exportaciones(ExportConcurrencyService $concurrencia)
    {
        $exportaciones = Export::orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $exportaciones,
            'capacidad' => $concurrencia->capacidad(),
        ]);
    }
}
