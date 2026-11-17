<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LimpiezaController extends Controller
{
    public function temporales()
    {
        $directorios = glob(storage_path('app/temp_pdf_*')) ?: [];
        $archivos = glob(storage_path('app/temp_*.pdf')) ?: [];

        $borradosDirs = 0;
        foreach ($directorios as $dir) {
            if (is_dir($dir) && File::deleteDirectory($dir)) {
                $borradosDirs++;
            }
        }

        $borradosArchivos = 0;
        foreach ($archivos as $archivo) {
            if (is_file($archivo) && @unlink($archivo)) {
                $borradosArchivos++;
            }
        }

        Audit::log('limpieza.temporales', [
            'descripcion' => 'Limpieza de temporales de exportacion',
            'datos' => ['directorios' => $borradosDirs, 'archivos' => $borradosArchivos],
        ]);

        return response()->json([
            'success' => true,
            'directorios_eliminados' => $borradosDirs,
            'archivos_eliminados' => $borradosArchivos,
        ]);
    }

    public function logs(Request $request)
    {
        $conservar = min(max((int) $request->input('lineas', 1000), 1), 100000);
        $ruta = storage_path('logs/laravel.log');

        if (!is_file($ruta)) {
            return response()->json([
                'success' => true,
                'lineas_conservadas' => 0,
                'bytes_antes' => 0,
                'bytes_despues' => 0,
            ]);
        }

        $bytesAntes = (int) filesize($ruta);
        $lineas = file($ruta) ?: [];
        $total = count($lineas);
        $conservadas = $total > $conservar ? array_slice($lineas, -$conservar) : $lineas;

        file_put_contents($ruta, implode('', $conservadas));

        $bytesDespues = (int) filesize($ruta);

        Audit::log('limpieza.logs', [
            'descripcion' => 'Truncado de laravel.log conservando ' . count($conservadas) . ' de ' . $total . ' lineas',
            'datos' => ['lineas_antes' => $total, 'lineas_despues' => count($conservadas), 'bytes_antes' => $bytesAntes, 'bytes_despues' => $bytesDespues],
        ]);

        return response()->json([
            'success' => true,
            'lineas_antes' => $total,
            'lineas_conservadas' => count($conservadas),
            'bytes_antes' => $bytesAntes,
            'bytes_despues' => $bytesDespues,
        ]);
    }

    public function tokens()
    {
        $eliminados = DB::table('personal_access_tokens')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        Audit::log('limpieza.tokens', [
            'descripcion' => "Poda de {$eliminados} tokens expirados",
            'datos' => ['tokens_eliminados' => $eliminados],
        ]);

        return response()->json([
            'success' => true,
            'tokens_eliminados' => $eliminados,
        ]);
    }
}
