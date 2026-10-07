<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\Descripcion_nota;

return new class extends Migration
{
    /**
     * Ejecutar migración.
     */
    public function up(): void
    {
        DB::transaction(function () {

            /*
             * Congelamos el último ID existente antes
             * de comenzar.
             *
             * Esto evita que los registros creados
             * durante la migración vuelvan a ser
             * procesados por chunkById().
             */
            $maxId = Descripcion_nota::max('id');

            if (!$maxId) {
                return;
            }

            Descripcion_nota::query()
                ->where('id', '<=', $maxId)
                ->orderBy('id')
                ->chunkById(
                    100,
                    function ($registros) {

                        foreach ($registros as $registro) {

                            $this->procesarRegistro($registro);
                        }
                    },
                    'id'
                );
        });
    }

    /**
     * Procesar un registro individual.
     */
    private function procesarRegistro(
        Descripcion_nota $registro
    ): void {

        $descripcion = trim(
            (string) $registro->descripcion
        );

        /*
         * No procesar descripciones vacías.
         */
        if ($descripcion === '') {
            return;
        }

        /*
         * No procesar rangos de turno.
         *
         * Ej:
         *
         * Hora: 14:00 - 15:00
         * Hora: 14:00 – 15:00
         * Hora: 2:00 PM - 3:00 PM
         */
        if ($this->esRangoDeTurno($descripcion)) {
            return;
        }

        /*
         * La hora almacenada actualmente en la fila
         * será considerada la hora principal.
         */
        $horaPrincipal = $this->normalizarHora(
            $registro->hora
        );

        /*
         * Si la fila no tiene una hora válida,
         * no intentamos reconstruirla.
         */
        if (!$horaPrincipal) {
            return;
        }

        /*
         * Buscar todas las horas dentro del texto.
         */
        preg_match_all(
            '/
                (?:
                    \b(?:hora)\s*:?\s*
                )?

                \b
                \d{1,2}

                (?:
                    :\d{2}
                )?

                \s*

                (?:
                    a\.?\s*m\.?
                    |
                    p\.?\s*m\.?
                    |
                    m\.?
                )?

            /ixu',
            $descripcion,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (empty($matches[0])) {
            return;
        }

        /*
         * Horas encontradas dentro de la descripción.
         */
        $cortes = [];

        foreach ($matches[0] as $match) {

            $horaTexto = trim(
                $match[0]
            );

            $horaNormalizada = $this->normalizarHora(
                $horaTexto
            );

            /*
             * Formato inválido.
             *
             * Ej:
             * 3:pm
             */
            if (!$horaNormalizada) {
                continue;
            }

            /*
             * Ignorar la misma hora que ya pertenece
             * al registro original.
             */
            if (
                $horaNormalizada === $horaPrincipal
            ) {
                continue;
            }

            $cortes[] = [
                'hora' => $horaNormalizada,
                'posicion' => $match[1],
            ];
        }

        /*
         * No existe una segunda hora.
         */
        if (empty($cortes)) {
            return;
        }

        /*
         * Ordenar cronológicamente según la posición
         * en la descripción.
         */
        usort(
            $cortes,
            fn ($a, $b) =>
                $a['posicion'] <=> $b['posicion']
        );

        /*
         * Construcción de segmentos.
         */
        $segmentos = [];

        $inicio = 0;

        $horaActual = $horaPrincipal;

        foreach ($cortes as $corte) {

            $texto = substr(
                $descripcion,
                $inicio,
                $corte['posicion'] - $inicio
            );

            $texto = $this->limpiarDescripcion(
                $texto
            );

            if ($texto !== '') {

                $segmentos[] = [
                    'hora' => $horaActual,
                    'descripcion' => $texto,
                ];
            }

            /*
             * La siguiente descripción comienza
             * con la nueva hora encontrada.
             */
            $horaActual = $corte['hora'];

            $inicio = $corte['posicion'];
        }

        /*
         * Último segmento.
         */
        $ultimoTexto = substr(
            $descripcion,
            $inicio
        );

        $ultimoTexto = $this->limpiarDescripcion(
            $ultimoTexto
        );

        if ($ultimoTexto !== '') {

            $segmentos[] = [
                'hora' => $horaActual,
                'descripcion' => $ultimoTexto,
            ];
        }

        /*
         * Eliminar segmentos demasiado pequeños.
         *
         * Se utiliza mb_strlen para soportar
         * correctamente caracteres UTF-8.
         */
        $segmentos = array_values(
            array_filter(
                $segmentos,
                function ($segmento) {

                    return mb_strlen(
                        trim($segmento['descripcion'])
                    ) >= 15;
                }
            )
        );

        /*
         * Si después de validar solo queda un segmento,
         * no modificar absolutamente nada.
         */
        if (count($segmentos) < 2) {
            return;
        }

        /*
         * Actualizar registro original.
         */
        $registro->update([
            'hora' => $segmentos[0]['hora'],

            'descripcion' => $segmentos[0]['descripcion'],
        ]);

        /*
         * Crear los registros adicionales.
         */
        foreach (
            array_slice($segmentos, 1)
            as $segmento
        ) {

            Descripcion_nota::create([

                'id_nota' =>
                    $registro->id_nota,

                'hora' =>
                    $segmento['hora'],

                'tipo' =>
                    $registro->tipo,

                'descripcion' =>
                    $segmento['descripcion'],

                /*
                 * Conservamos la fecha de creación
                 * del registro original.
                 */
                'created_at' =>
                    $registro->created_at,

                /*
                 * Este sí representa la fecha
                 * en que se realizó la migración.
                 */
                'updated_at' =>
                    now(),
            ]);
        }
    }

    /**
     * Determina si una descripción representa
     * un rango de turno.
     */
    private function esRangoDeTurno(string $descripcion): bool
    {
        return preg_match(
            '/
                \bHORA\s*:?\s*
                (?:[01]?[0-9]|2[0-3]):[0-5][0-9]
                \s*
                [-–]
                \s*
                (?:[01]?[0-9]|2[0-3]):[0-5][0-9]
                (?![\p{L}\p{N}])
            /ixu',
            $descripcion
        ) === 1;
    }

    /**
     * Limpia la descripción después de separar
     * el encabezado de hora.
     */
    private function limpiarDescripcion(string $texto): string
    {
        $texto = trim($texto);

        /*
        * Elimina únicamente encabezados con formato:
        *
        * 00:00: Texto
        * 0:00: Texto
        * Hora: 00:00: Texto
        *
        * No elimina:
        * 3.pm
        * 3:pm
        * 18CC
        */
        $texto = preg_replace(
            '/^\s*
                (?:
                    \bHORA\s*:?\s*
                )?
                (?:[01]?[0-9]|2[0-3]):[0-5][0-9]
                \s*
                (?:
                    A\.?\s*M\.?
                    |
                    P\.?\s*M\.?
                )?
                (?![\p{L}\p{N}])
                \s*
                [:.\-]?
                \s*
            /ixu',
            '',
            $texto
        );

        return trim($texto);
    }

    /**
     * Normaliza diferentes representaciones
     * de hora a HH:mm.
     */
    private function normalizarHora(?string $hora): ?string
    {
        if (!$hora) {
            return null;
        }

        $hora = trim($hora);

        $hora = preg_replace(
            '/^\s*HORA\s*:?\s*/iu',
            '',
            $hora
        );

        $hora = preg_replace('/\s+/', ' ', $hora);

        $hora = preg_replace(
            '/A\.?\s*M\.?/iu',
            'AM',
            $hora
        );

        $hora = preg_replace(
            '/P\.?\s*M\.?/iu',
            'PM',
            $hora
        );

        $hora = trim($hora);

        /*
        * Formato estricto de 24 horas:
        *
        * 0:00
        * 00:00
        * 9:30
        * 09:30
        * 14:00
        * 23:59
        *
        * NO:
        * 3.pm
        * 3:pm
        * 18CC
        */
        if (
            preg_match(
                '/^(?:[01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                $hora
            )
        ) {
            $fecha = \DateTime::createFromFormat(
                'G:i',
                $hora
            );

            if ($fecha !== false) {
                $errores = \DateTime::getLastErrors();

                if (
                    !is_array($errores)
                    ||
                    (
                        $errores['warning_count'] === 0
                        &&
                        $errores['error_count'] === 0
                    )
                ) {
                    return $fecha->format('H:i');
                }
            }
        }

        /*
        * Se mantienen los formatos AM/PM únicamente para
        * normalizar correctamente el valor almacenado en
        * $registro->hora.
        */
        if (
            preg_match(
                '/^(0?[1-9]|1[0-2])(?::[0-5][0-9])?\s*(AM|PM)$/i',
                $hora
            )
        ) {
            $formatos = [
                'g:i A',
                'g A',
                'g:iA',
                'gA',
            ];

            foreach ($formatos as $formato) {
                $fecha = \DateTime::createFromFormat(
                    $formato,
                    strtoupper($hora)
                );

                if ($fecha === false) {
                    continue;
                }

                $errores = \DateTime::getLastErrors();

                if (
                    is_array($errores)
                    &&
                    (
                        $errores['warning_count'] > 0
                        ||
                        $errores['error_count'] > 0
                    )
                ) {
                    continue;
                }

                return $fecha->format('H:i');
            }
        }

        return null;
    }

    /**
     * Rollback.
     *
     * No se puede reconstruir de forma segura la
     * descripción original únicamente con los registros
     * generados, por lo que no se elimina automáticamente.
     */
    public function down(): void
    {
        /*
         * Esta migración modifica datos existentes y crea
         * registros adicionales.
         *
         * Para producción se recomienda hacer backup antes
         * de ejecutarla.
         */
    }
};