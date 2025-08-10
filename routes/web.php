<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Oauth\OAuthController;
use App\Http\Controllers\Oauth\LoginOAuthController;

use Illuminate\Http\Request;




// Ruta inicial de autorización OAuth para iOS y Android
Route::get('/oauth/authorize', [OAuthController::class, 'authorizeRequest']);



Route::middleware('web')->group(function () {

    // Página de login (formulario)
    Route::get('/login-cliente', function () {
        return view('auth.login');
    })->name('cliente.login');

    // Procesar login (manda OTP y redirige)
    Route::post('/login-cliente', [LoginOAuthController::class, 'loginBlade'])->name('cliente.login.form');


    // Ruta para reenviar OTP (desde la vista)
    Route::post('/cliente/resend-otp', [LoginOAuthController::class, 'resendOTPBlade'])->name('cliente.resendOtp');


    
    // Página para ingresar el OTP (vista con campos ocultos desde la sesión) 
    Route::get('/otp-cliente', function () {
        return view('auth.otp', [
            'email' => session('otp_email'),
            'client_id' => session('client_id'),
            'redirect_uri' => session('redirect_uri'),
            'state' => session('state'),
        ]);
    })->name('cliente.otp');

    // Procesar verificación del OTP
    Route::post('/verificar-otp-cliente', [LoginOAuthController::class, 'verifyOtpBlade'])->name('cliente.otp.verify.form');



    //ruta corpus
    Route::get('/oauth/callback', function (Request $request) {
    $code = $request->query('code');
    $state = $request->query('state');

    if (!$code) {
        abort(400, 'Missing authorization code.');
    }

    // Opcionalmente podrías validar el state aquí

    // Redirige al esquema de escritorio
    return redirect("myapp://callback?code={$code}&state={$state}");
    })->name('oauth.callback');
});
