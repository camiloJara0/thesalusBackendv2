<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\ErrorLog;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Foundation\Exceptions\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            try {
                ErrorLog::create([
                    'level' => 'error',
                    'message' => Str::limit($e->getMessage() ?: class_basename($e), 4000, '...'),
                    'fingerprint' => sha1(class_basename($e) . '|' . $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine()),
                    'context' => $this->contextoError($e),
                    'occurred_at' => now(),
                ]);
            } catch (Throwable $captura) {
                // La captura nunca debe empeorar el error original
            }
        });
    }

    private function contextoError(Throwable $e): array
    {
        $request = request();
        $payload = [];

        try {
            if ($request?->isJson()) {
                $payload = $request->json()->all();
            } elseif ($request) {
                $payload = $request->input();
            }
        } catch (Throwable $ignore) {
            $payload = [];
        }

        return [
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'user_id' => Auth::id(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'payload' => $this->payloadSeguro($payload),
        ];
    }

    private function payloadSeguro(array $payload): array
    {
        $clavesSecretas = ['password', 'contraseña', 'token', 'authorization', 'access_token', 'secret'];

        $seguro = [];
        foreach ($payload as $clave => $valor) {
            if (is_string($valor) && Str::contains(Str::lower($clave), $clavesSecretas)) {
                $seguro[$clave] = '***';
            } elseif (is_array($valor)) {
                $seguro[$clave] = $this->payloadSeguro($valor);
            } else {
                $seguro[$clave] = $valor;
            }
        }

        $json = json_encode($seguro, JSON_UNESCAPED_UNICODE);
        if (strlen($json) > 2048) {
            return ['_truncado' => true, 'bytes' => strlen($json)];
        }

        return $seguro;
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Formato estandarizado de errores API:
     * { success, message, codigo, accion, errors? }
     *
     * codigo: validacion | autenticacion | permiso | no_encontrado |
     *         conflicto | solicitud | rate_limit | servidor
     * accion: texto orientado a la accion para el usuario final.
     */
    private function handleApiException($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'codigo'  => 'validacion',
                'accion'  => 'Revise los campos marcados y corrija la información.',
                'message' => 'Datos inválidos. Corrija los campos indicados.',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json([
                'success' => false,
                'codigo'  => 'no_encontrado',
                'accion'  => 'El registro puede haber sido eliminado por otro usuario. Actualice la información.',
                'message' => "{$model} no encontrado",
            ], 404);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'codigo'  => 'autenticacion',
                'accion'  => 'Vuelva a iniciar sesión.',
                'message' => 'No autenticado',
            ], 401);
        }

        if ($e instanceof TokenMismatchException) {
            return response()->json([
                'success' => false,
                'codigo'  => 'autenticacion',
                'accion'  => 'Actualice la página e intente nuevamente.',
                'message' => 'Token CSRF inválido',
            ], 419);
        }

        if ($e instanceof HttpException && $e->getStatusCode() === 404) {
            return response()->json([
                'success' => false,
                'codigo'  => 'no_encontrado',
                'accion'  => 'Verifique la dirección o actualice la lista de registros.',
                'message' => 'Recurso no encontrado',
            ], 404);
        }

        if ($e instanceof HttpException && $e->getStatusCode() === 403) {
            return response()->json([
                'success' => false,
                'codigo'  => 'permiso',
                'accion'  => 'Solicite acceso al administrador o use la opción de solicitud de permisos.',
                'message' => 'No tiene permisos para realizar esta acción',
            ], 403);
        }

        if ($e instanceof HttpException && $e->getStatusCode() === 409) {
            return response()->json([
                'success' => false,
                'codigo'  => 'conflicto',
                'accion'  => 'Actualice la información e intente nuevamente.',
                'message' => 'Conflicto con el estado actual del registro',
            ], 409);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return response()->json([
                'success' => false,
                'codigo'  => 'rate_limit',
                'accion'  => 'Espere unos segundos e intente nuevamente.',
                'message' => 'Demasiadas solicitudes. Intente nuevamente en unos segundos.',
            ], 429);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'codigo'  => 'solicitud',
                'accion'  => 'Verifique los datos enviados e intente nuevamente.',
                'message' => 'Error en la solicitud',
            ], $e->getStatusCode());
        }

        \Log::error('Error interno no controlado', [
            'exception' => $e,
            'url' => $request->url(),
            'method' => $request->method(),
        ]);

        $message = config('app.debug')
            ? $e->getMessage()
            : 'Error interno del servidor. Intente nuevamente; si persiste contacte al administrador.';

        return response()->json([
            'success' => false,
            'codigo'  => 'servidor',
            'accion'  => 'Intente nuevamente; si persiste contacte al administrador.',
            'message' => $message,
        ], 500);
    }

    protected function unauthenticated($request, AuthenticationException $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        return parent::unauthenticated($request, $e);
    }
}
