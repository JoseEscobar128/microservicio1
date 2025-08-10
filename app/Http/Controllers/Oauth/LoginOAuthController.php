<?php

namespace App\Http\Controllers\Oauth;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Laravel\Facades\Resend;
use Illuminate\Routing\Controller;

class LoginOAuthController extends Controller
{
    /**
     * Maneja el login del cliente desde la vista (Blade).
     * Valida credenciales, verifica correo y envía OTP.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginBlade(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string'
        ]);


        $login = $request->login;
        $password = $request->password;

        // Primero intentar con clientes
        $cliente = \App\Models\Cliente::where('email', $login)->first();


        if ($cliente && Hash::check($password, $cliente->contrasena_hash)) {
            if (!$cliente->email_verificado) {
                return back()->with('error', 'Correo no verificado.');
            }

            $this->generarYEnviarOTP($cliente); // ya existente
            session([
                'tipo_usuario' => 'cliente',
                'otp_email' => $login,
                'client_id' => $request->client_id,
                'redirect_uri' => $request->redirect_uri,
                'state' => $request->state,
            ]);

            return redirect()->route('cliente.otp');
        }

        // Si no es cliente, intenta con usuario interno
        $usuario = \App\Models\Usuario::where(function ($query) use ($login) {
            $query->where('email', $login)
                ->orWhere('usuario', $login); // usa el mismo campo para aceptar usuario o correo
        })->first();


        if ($usuario && Hash::check($password, $usuario->contrasena_hash)) {
            if (!$usuario->email_verificado) {
                return back()->with('error', 'Correo no verificado.');
            }

            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $usuario->update([
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(10)
            ]);

            $this->sendOTPEmail($usuario->email, $otp, $usuario->usuario);

            session([
                'tipo_usuario' => 'usuario',
                'otp_email' => $usuario->email,
                'client_id' => $request->client_id,
                'redirect_uri' => $request->redirect_uri,
                'state' => $request->state,
            ]);

            return redirect()->route('cliente.otp');
        }

        return back()->with('error', 'Credenciales incorrectas');
    }


    /**
     * Verifica el OTP desde la vista (Blade) y redirige con el código OAuth.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyOtpBlade(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits_between:4,6',
            'client_id' => 'required',
            'redirect_uri' => ['required', function ($attribute, $value, $fail) {
                $validSchemes = ['http', 'https', 'miapp'];
                $scheme = parse_url($value, PHP_URL_SCHEME);
                if (!$scheme || !in_array(strtolower($scheme), $validSchemes)) {
                    $fail("El $attribute debe tener un esquema válido (http, https o miapp).");
                }
            }],
            'tipo_usuario' => 'required|in:cliente,usuario',
            'state' => 'nullable'
        ]);

        $tipo = $request->input('tipo_usuario');
        $email = $request->email;

        try {
            $user = $tipo === 'cliente'
                ? \App\Models\Cliente::where('email', $email)->first()
                : \App\Models\Usuario::where('email', $email)->first();

            if (!$user || $user->otp_code !== $request->otp || now()->gt($user->otp_expires_at)) {
                return back()->withErrors(['otp' => 'Código incorrecto o expirado']);
            }

            $user->update([
                'otp_code' => null,
                'otp_expires_at' => null,
                'otp_verified_at' => now()
            ]);

            $client = \App\Models\OAuthClient::where('client_id', $request->client_id)
                ->where('redirect_uri', $request->redirect_uri)
                ->first();

            if (!$client) {
                return redirect()->route('cliente.login')->with('error', 'Cliente OAuth inválido');
            }

            $code = Str::random(40);
            Log::info("verifyOtpBlade: tipo_usuario=$tipo, user_id=" . ($user->id ?? 'null'));

            \App\Models\OAuthAuthorizationCode::create([
                'cliente_id' => $tipo === 'cliente' ? $user->id : null,
                'usuario_id' => $tipo === 'usuario' ? $user->id : null,
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
            ]);

            return redirect()->to($request->redirect_uri . "?code=$code&state=" . urlencode($request->state));
        } catch (\Throwable $e) {
            Log::error("Error verificando OTP ($tipo): " . $e->getMessage());
            return back()->with('error', 'Error verificando OTP');
        }
    }



    /**
     * Reenvía el código OTP a través de la web (Blade).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendOtpBlade(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
            'usuario' => 'nullable|string',
        ]);

        if (!$request->filled('email') && !$request->filled('usuario')) {
            return redirect()->back()->with('error', 'Debes proporcionar un correo electrónico o nombre de usuario.');
        }

        try {
            if ($request->filled('email')) {
                // Buscar en clientes primero
                $cliente = Cliente::where('email', $request->email)->first();

                if ($cliente) {
                    $this->generarYEnviarOTP($cliente);
                    return redirect()->back()->with('success', 'Se ha enviado un nuevo código OTP a tu correo.');
                }

                // Si no está en clientes, buscar en usuarios
                $usuario = \App\Models\Usuario::where('email', $request->email)->first();
                if ($usuario) {
                    $this->generarYEnviarOTPUsuario($usuario);
                    return redirect()->back()->with('success', 'Se ha enviado un nuevo código OTP a tu correo.');
                }

                return redirect()->back()->with('error', 'No se encontró usuario con ese correo electrónico.');
            }

            if ($request->filled('usuario')) {
                // Buscar en usuarios por nombre de usuario
                $usuario = \App\Models\Usuario::where('usuario', $request->usuario)->first();

                if ($usuario) {
                    $this->generarYEnviarOTPUsuario($usuario);
                    return redirect()->back()->with('success', 'Se ha enviado un nuevo código OTP a tu correo.');
                }

                return redirect()->back()->with('error', 'No se encontró usuario con ese nombre de usuario.');
            }
        } catch (\Throwable $e) {
            Log::error("Error al reenviar OTP desde Blade: " . $e->getMessage());
            return redirect()->back()->with('error', 'No se pudo reenviar el código OTP. Intenta más tarde.');
        }
    }

    /**
     * Genera OTP para usuario (tabla usuarios) y envía email.
     *
     * @param \App\Models\Usuario $usuario
     * @return void
     */
    private function generarYEnviarOTPUsuario($usuario): void
    {
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $usuario->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        $this->sendOTPEmail($usuario->email, $otp, $usuario->usuario);
    }


    /**
     * Genera un código OTP de 4 dígitos y lo guarda junto con su expiración.
     *
     * @param Cliente $cliente
     * @return void
     */
    private function generarYEnviarOTP(Cliente $cliente): void
    {
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $cliente->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        $this->sendOTPEmail($cliente->email, $otp, $cliente->nombre);
    }

    /**
     * Envía el código OTP al correo del cliente usando Resend.
     *
     * @param string $email
     * @param string $otp
     * @param string $nombre
     * @return void
     */
    protected function sendOTPEmail(string $email, string $otp, string $nombre): void
    {
        try {
            $html = view('emails.otp', compact('nombre', 'otp'))->render();

            $response = Resend::emails()->send([
                'from' => env('MAIL_FROM_ADDRESS'), 
                'to' => [$email],
                'subject' => 'Tu código de verificación',
                'html' => $html,
            ]);

            Log::info('Correo OTP enviado', [
                'to' => $email,
                'otp' => $otp,
                'resend_id' => $response['id'] ?? null
            ]);
        } catch (\Throwable $e) {
            Log::error("Error enviando OTP con Resend: " . $e->getMessage());
        }
    }
}
