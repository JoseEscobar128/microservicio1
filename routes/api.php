<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\RolController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Oauth\OAuthController;
use App\Http\Controllers\Api\ClienteApiController;
use App\Http\Controllers\Api\EmpleadoController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\RolController;

Route::prefix('v1')->group(function () {

    Route::prefix('empleados')->group(function () {
        Route::post('/register', [EmpleadoController::class, 'store']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/', [EmpleadoController::class, 'index'])->middleware('permission:empleados.view_all,api');
            Route::get('/{id}', [EmpleadoController::class, 'show'])->middleware('permission:empleados.view,api');
            Route::put('/{id}', [EmpleadoController::class, 'update'])->middleware('permission:empleados.update,api');
            Route::delete('/{id}', [EmpleadoController::class, 'destroy'])->middleware('permission:empleados.delete,api');
        });
    });

    Route::prefix('usuarios')->group(function () {
        Route::post('register', [UsuarioController::class, 'store']);
        Route::post('verify-otp', [UsuarioController::class, 'verifyOTP']);
        Route::post('resend-otp', [UsuarioController::class, 'resendOTP']);
        Route::post('logout', [UsuarioController::class, 'logout'])->middleware('auth:sanctum');


        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [UsuarioController::class, 'profile']);
            Route::put('/{id}', [UsuarioController::class, 'update'])->middleware('permission:usuarios.update,api');
            Route::get('/', [UsuarioController::class, 'index'])->middleware('permission:usuarios.view_all,api');
            Route::get('/{id}', [UsuarioController::class, 'show'])->middleware('permission:usuarios.view,api');
            Route::delete('/{id}', [UsuarioController::class, 'destroy'])->middleware('permission:usuarios.delete,api');
        });
    });


    Route::prefix('clientes')->group(function () {
        // Rutas públicas
        Route::post('registro', [ClienteApiController::class, 'store']);
        Route::post('verify-otp', [ClienteApiController::class, 'verifyLoginOTP']);
        Route::post('reenviar-otp', [ClienteApiController::class, 'resendOTP']);

        // Rutas protegidas para usuario autenticado
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/', [ClienteApiController::class, 'index'])->middleware('permission:clientes.view_all');
            Route::get('/{id}', [ClienteApiController::class, 'show']);
            Route::put('/{id}', [ClienteApiController::class, 'update'])->middleware('permission:cliente.update');
            Route::delete('/{id}', [ClienteApiController::class, 'destroy'])->middleware('permission:cliente.delete');
            Route::post('logout', [ClienteApiController::class, 'logout']);
        });
    });


    // Rutas para que el OAuth pida el token de acceso por un codigo de autorización
    Route::prefix('oauth2')->group(function () {
        Route::post('/token', [OAuthController::class, 'token']);
    });

    // Ruta para regresar al inicio de sesión si no está autenticado (no borrar)
    Route::any('/login', function () {
        return response()->json([
            'status' => 'error',
            'message' => 'Debes estar autenticado para usar esta ruta.'
        ], 401);
    })->name('login');

    Route::prefix('roles')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', [RolController::class, 'indexRoles'])->middleware('permission:roles.view_all,api');
            Route::post('/', [RolController::class, 'storeRole'])->middleware('permission:roles.create,api');
            Route::get('/{id}', [RolController::class, 'showRole'])->middleware('permission:roles.view,api');
            Route::put('/{id}', [RolController::class, 'updateRole'])->middleware('permission:roles.update,api');
            Route::delete('/{id}', [RolController::class, 'destroyRole'])->middleware('permission:roles.delete,api');
            Route::patch('/{id}', [RolController::class, 'removePermission'])->middleware('permission:roles.update,api');
        });
    });

    Route::prefix('permisos')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            // Listar todos los permisos
            Route::get('/', [RolController::class, 'indexPermisos'])->middleware('permission:permisos.view_all,api');
            Route::post('/', [RolController::class, 'storePermiso'])
                ->middleware('permission:permisos.create');
            Route::get('/{id}', [RolController::class, 'showPermiso'])
                ->middleware('permission:permisos.view');
            Route::put('/{id}', [RolController::class, 'updatePermiso'])
                ->middleware('permission:permisos.update');
            Route::delete('/{id}', [RolController::class, 'destroyPermiso'])
                ->middleware('permission:permisos.delete');
        });
    });
});
