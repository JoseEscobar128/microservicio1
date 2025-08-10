<?php

namespace App\Http\Controllers\Oauth;

use App\Models\OAuthClient;
use App\Models\OAuthAuthorizationCode;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
use App\Services\PermisoService;


class OAuthController extends Controller
{


    /**
     * Autoriza una solicitud OAuth redireccionando al login del cliente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function authorizeRequest(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'redirect_uri' => ['required', function ($attribute, $value, $fail) {
                $validSchemes = ['http', 'https', 'miapp'];
                $scheme = parse_url($value, PHP_URL_SCHEME);
                if (!$scheme || !in_array(strtolower($scheme), $validSchemes)) {
                    $fail("El $attribute debe tener un esquema válido (http, https o miapp).");
                }
            }],
            'state' => 'nullable'
        ]);

        $client = OAuthClient::where('client_id', $request->client_id)->first();

        if (!$client) {
            return response()->json(['error' => 'Cliente OAuth inválido'], 403);
        }

        if ($client->redirect_uri !== $request->redirect_uri) {
            return response()->json(['error' => 'Redirect URI inválido'], 403);
        }

        session([
            'client_id' => $client->client_id,
            'redirect_uri' => $client->redirect_uri,
            'state' => $request->state
        ]);

        return redirect()->route('cliente.login', [
            'client_id' => $request->client_id,
            'redirect_uri' => $request->redirect_uri,
            'state' => $request->state,
        ]);
    }

    /**
     * Intercambia el código de autorización por un token de acceso.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function token(Request $request)
    {
        $request->validate([
            'grant_type' => 'required|in:authorization_code',
            'code' => 'required|string',
            'redirect_uri' => ['required', function ($attribute, $value, $fail) {
                $validSchemes = ['http', 'https', 'miapp'];
                $scheme = parse_url($value, PHP_URL_SCHEME);
                if (!$scheme || !in_array(strtolower($scheme), $validSchemes)) {
                    $fail("El $attribute debe tener un esquema válido (http, https o miapp).");
                }
            }],
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        Log::info('Token request recibida', $request->except('client_secret'));

        $client = OAuthClient::where('client_id', $request->client_id)
            ->where('redirect_uri', $request->redirect_uri)
            ->first();

        if (!$client || !Hash::check($request->client_secret, $client->client_secret)) {
            Log::warning('Cliente OAuth inválido');
            return response()->json(['error' => 'Cliente OAuth inválido'], 401);
        }

        $authCode = OAuthAuthorizationCode::where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$authCode) {
            Log::warning('Código de autorización inválido o expirado');
            return response()->json(['error' => 'Código de autorización inválido o expirado'], 400);
        }

        $token = null;
        $tipo = null;
        $nombre = null;
        $rol = null;
        //$permisos = [];

        if ($authCode->cliente_id) {
            $cliente = \App\Models\Cliente::find($authCode->cliente_id);
            if (!$cliente) {
                Log::warning('Cliente asociado al código no encontrado');
                return response()->json(['error' => 'Cliente no encontrado'], 400);
            }

            $token = $cliente->createToken('access_token')->plainTextToken;
            $tipo = 'cliente';
            $nombre = $cliente->nombre;
        } elseif ($authCode->usuario_id) {
            $usuario = \App\Models\Usuario::with('empleado.rol')->find($authCode->usuario_id);

            if (!$usuario) {
                Log::warning('Usuario asociado al código no encontrado');
                return response()->json(['error' => 'Usuario no encontrado'], 400);
            }

            $rol = 'sin rol asignado'; // Valor por defecto

            if ($usuario->empleado) {
                if (method_exists($usuario->empleado, 'getRoleNames')) {
                    // Si usas Spatie Roles
                    $rol = $usuario->empleado->getRoleNames()->first() ?? $rol;
                } elseif ($usuario->empleado->rol) {
                    // Si tienes relación belongsTo Rol
                    $rol = $usuario->empleado->rol->nombre ?? $rol;
                }
            }

            Log::info("Rol asignado para usuario {$usuario->usuario}: $rol");
            $token = $usuario->createToken('access_token')->plainTextToken;
            $tipo = 'empleado';
            $nombre = $usuario->usuario;
          
        } else {
            Log::warning('Código de autorización sin cliente_id ni usuario_id');
            return response()->json(['error' => 'Código de autorización inválido'], 400);
        }

        $authCode->delete();

        Log::info("Token generado exitosamente para $tipo: $nombre");

        return response()->json([
            'status' => 'success',
            'message' => 'Token generado correctamente',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hora
            'tipo_usuario' => $tipo,
            'nombre' => $nombre,
            'rol' => $rol,

        ]);
    }
}
