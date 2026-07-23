<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
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
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json([
                'success' => false,
                'message' => "{$model} no encontrado",
            ], 404);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        if ($e instanceof TokenMismatchException) {
            return response()->json([
                'success' => false,
                'message' => 'Token CSRF inválido',
            ], 419);
        }

        if ($e instanceof HttpException && $e->getStatusCode() === 404) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado',
            ], 404);
        }

        if ($e instanceof HttpException && $e->getStatusCode() === 403) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción',
            ], 403);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiadas solicitudes. Intente nuevamente en unos segundos.',
            ], 429);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la solicitud',
            ], $e->getStatusCode());
        }

        \Log::error('Error interno no controlado', [
            'exception' => $e,
            'url' => $request->url(),
            'method' => $request->method(),
        ]);

        $message = config('app.debug') ? $e->getMessage() : 'Error interno del servidor';

        return response()->json([
            'success' => false,
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
