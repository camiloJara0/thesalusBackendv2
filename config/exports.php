<?php

return [

    /*
    * Cantidad máxima de exportaciones activas (pendiente, procesando,
    * comprimiendo) en todo el sistema. Protege MySQL y la cola en
    * hosting compartido.
    */
    'max_activas' => (int) env('EXPORT_MAX_ACTIVAS', 6),

    /*
    * Horas tras las cuales una exportación aún activa se considera
    * vencida (worker murió sin actualizar el estado) y pasa a fallida,
    * liberando su cupo.
    */
    'horas_vencidas' => (int) env('EXPORT_HORAS_VENCIDAS', 2),

    /*
    * Repositorio de PDFs por análisis en AWS S3.
    * Cada PDF se genera una sola vez y se reutiliza en todas las
    * exportaciones posteriores (los análisis históricos se migran
    * de forma perezosa: se generan bajo demanda en su primer uso).
    */
    'pdf' => [
        'disco' => env('EXPORT_PDF_DISK', 's3'),
        'carpeta' => env('EXPORT_PDF_CARPETA', 'pdfs-analisis'),
        'reutilizar' => (bool) env('EXPORT_REUTILIZAR_PDFS', true),
        'generar_al_guardar' => (bool) env('EXPORT_PDF_GENERAR_AL_GUARDAR', true),
    ],

];
