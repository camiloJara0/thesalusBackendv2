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

];
