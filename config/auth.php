<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => 'api', // El guard por defecto para esta API será 'api'
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users', // Correcto: usa el proveedor 'users'
        ],

        'cliente' => [
            'driver' => 'sanctum',
            'provider' => 'clientes',
        ],

        'empleado' => [
            'driver' => 'sanctum',
            'provider' => 'empleados',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        // ¡LA CORRECCIÓN CLAVE ESTÁ AQUÍ!
        // Le decimos al proveedor 'users' que use tu modelo 'Usuario'.
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\Usuario::class,
        ],

        'clientes' => [
            'driver' => 'eloquent',
            'model' => App\Models\Cliente::class,
        ],

        'empleados' => [
            'driver' => 'eloquent',
            'model' => App\Models\Empleado::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => 10800,

];