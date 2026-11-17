<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permisos base (siempre otorgados)
    |--------------------------------------------------------------------------
    |
    | Permisos indispensables para el funcionamiento basico de la aplicacion
    | (catalogos, agenda, pacientes e historial). Se otorgan a todos los
    | usuarios sin necesidad de asignarlos y no se muestran en el formulario
    | de permisos del frontend. Espejo documentado de PERMISOS_BASE en
    | santaIsabel/data/permisos.js — mantener ambos listados sincronizados.
    |
    */

    'base' => [
        'Datos_get',
        'Citas_view',
        'Citas_get',
        'Pacientes_view',
        'Pacientes_get',
        'Historias_view',
        'Historias_get',
    ],

];
