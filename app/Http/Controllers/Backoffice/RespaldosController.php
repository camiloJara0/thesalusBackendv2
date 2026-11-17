<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RespaldosController extends Controller
{
    private function directorio(): string
    {
        $ruta = storage_path('app/backups');

        if (!is_dir($ruta)) {
            File::makeDirectory($ruta, 0755, true);
        }

        return $ruta;
    }

    public function index()
    {
        $archivos = collect(File::files($this->directorio()))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), ['.sql', '.sql.gz']))
            ->map(fn ($file) => [
                'archivo' => $file->getFilename(),
                'bytes' => $file->getSize(),
                'modificado' => $file->getMTime(),
            ])
            ->sortByDesc('modificado')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $archivos,
        ]);
    }

    public function store()
    {
        $rutaMysqldump = config('database.mysqldump_path');

        if (!$rutaMysqldump) {
            return response()->json([
                'success' => false,
                'message' => 'MYSQLDUMP_PATH no esta configurado en el servidor',
            ], 501);
        }

        $conexion = config('database.connections.' . config('database.default'));
        $nombre = 'respaldo-' . now()->format('Y-m-d-His') . '.sql';
        $destino = $this->directorio() . DIRECTORY_SEPARATOR . $nombre;

        $comando = [
            $rutaMysqldump,
            '-h', (string) $conexion['host'],
            '-P', (string) ($conexion['port'] ?? 3306),
            '-u', (string) $conexion['username'],
            '--password=' . ($conexion['password'] ?? ''),
            '--single-transaction',
            '--no-tablespaces',
            '--result-file=' . $destino,
            (string) $conexion['database'],
        ];

        $proceso = new Process($comando);
        $proceso->setTimeout(600);
        $proceso->run();

        if (!$proceso->isSuccessful()) {
            @unlink($destino);
            $error = Str::limit($proceso->getErrorOutput() ?: $proceso->getOutput(), 500, '...');

            if (!empty($conexion['password'])) {
                $error = str_replace($conexion['password'], '***', $error);
            }

            Audit::log('respaldos.crear', [
                'descripcion' => 'Respaldo fallido',
                'datos' => ['error' => $error],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'mysqldump fallo: ' . $error,
            ], 500);
        }

        $bytes = is_file($destino) ? (int) filesize($destino) : 0;

        Audit::log('respaldos.crear', [
            'descripcion' => "Respaldo {$nombre} creado ({$bytes} bytes)",
            'datos' => ['archivo' => $nombre, 'bytes' => $bytes],
        ]);

        return response()->json([
            'success' => true,
            'archivo' => $nombre,
            'bytes' => $bytes,
        ], 201);
    }

    public function descargar($archivo)
    {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(sql|sql\.gz)$/', $archivo)) {
            return response()->json([
                'success' => false,
                'message' => 'Nombre de archivo invalido',
            ], 404);
        }

        $base = realpath($this->directorio());
        $destino = realpath($this->directorio() . DIRECTORY_SEPARATOR . $archivo);

        if (!$base || !$destino || !str_starts_with($destino, $base . DIRECTORY_SEPARATOR) || !is_file($destino)) {
            return response()->json([
                'success' => false,
                'message' => 'Respaldo no encontrado',
            ], 404);
        }

        return response()->download($destino, $archivo);
    }
}
