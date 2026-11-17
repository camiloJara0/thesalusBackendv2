<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cuenta SuperAdmin del backoffice
    |--------------------------------------------------------------------------
    | Usada por database/seeders/SuperadminSeeder.php. La contraseña SIEMPRE
    | se define en .env (nunca en el repositorio): SUPERADMIN_PASSWORD.
    */

    'correo' => env('SUPERADMIN_CORREO', 'superadmin@santaisabel.com'),

    'password' => env('SUPERADMIN_PASSWORD'),

];
