<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Direccion;
use Resend\Laravel\Facades\Resend;
use Exception;
use Illuminate\Validation\ValidationException;

class ClienteApiController extends Controller
{
    /**
     * Registra un nuevo cliente y envía código OTP por correo.
     *
     * @param ClienteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ClienteRequest $request)
    {
        try {
            // Buscar cliente por email, incluso si está eliminado (soft deleted)
            $clienteExistente = Cliente::withTrashed()->where('email', $request->email)->first();

            if ($clienteExistente) {
                if ($clienteExistente->trashed()) {
                    // Restaurar cliente eliminado
                    $clienteExistente->restore();

                    // Actualizar datos con los del request
                    $clienteExistente->fill([
                        'nombre' => $request->nombre,
                        'apellido' => $request->apellido,
                        'contrasena_hash' => Hash::make($request->password),
                        'telefono' => $request->telefono,
                        // Añade aquí otros campos que deban actualizarse
                    ]);
                    $clienteExistente->save();

                    $this->generarYEnviarOTP($clienteExistente);

                    return response()->json([
                        'code' => 'CLI-002',
                        'status' => 'success',
                        'message' => 'Cliente restaurado y registrado. Verifica tu correo con el código.',
                        'data' => [
                            'email' => $clienteExistente->email,
                            'otp_expires_at' => $clienteExistente->otp_expires_at->toDateTimeString()
                        ]
                    ], Response::HTTP_OK);
                } else {
                    // Cliente activo ya existe con ese email
                    return response()->json([
                        'code' => 'CLI-003',
                        'status' => 'error',
                        'message' => 'El correo electrónico ya está registrado.',
                        'errors' => ['email' => ['Ya existe un cliente con este correo electrónico.']]
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            Log::info('Iniciando registro de cliente', ['email' => $request->email]);

            $clienteData = $request->except('direccion', 'password');
            $clienteData['contrasena_hash'] = Hash::make($request->password);
            $clienteData['otp_code'] = null;
            $clienteData['otp_expires_at'] = null;

            $cliente = DB::transaction(function () use ($clienteData, $request) {
                $direccion = Direccion::create($request->input('direccion'));
                $cliente = Cliente::make($clienteData);
                $cliente->direccion()->associate($direccion);
                $cliente->save();
                return $cliente;
            });

            $this->generarYEnviarOTP($cliente);

            return response()->json([
                'code' => 'CLI-002',
                'status' => 'success',
                'message' => 'Registro exitoso. Verifica tu correo con el código.',
                'data' => [
                    'email' => $cliente->email,
                    'otp_expires_at' => $cliente->otp_expires_at->toDateTimeString()
                ]
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Error en registro de cliente: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Ocurrió un error al registrar al cliente. Intenta más tarde.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Reenvía el código OTP si el correo aún no ha sido verificado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendOTP(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:clientes,email'
            ], [
                'email.required' => 'Debes proporcionar un correo electrónico.',
                'email.email' => 'El formato del correo no es válido.',
                'email.exists' => 'No hay ningún cliente registrado con ese correo.'
            ]);

            $cliente = Cliente::where('email', $request->email)->firstOrFail();

            if ($cliente->email_verificado) {
                return response()->json([
                    'code' => 'CLI-005',
                    'status' => 'error',
                    'message' => 'El correo ya fue verificado.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->generarYEnviarOTP($cliente);

            return response()->json([
                'code' => 'CLI-004',
                'status' => 'success',
                'message' => 'Nuevo código enviado a tu correo.',
                'data' => [
                    'otp_expires_at' => $cliente->otp_expires_at->toDateTimeString()
                ]
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'code' => 'CLI-001',
                'status' => 'error',
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Error al reenviar OTP: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al reenviar el código. Intenta más tarde.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verifica el código OTP enviado por el cliente para iniciar sesión.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyLoginOTP(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:clientes,email',
                'otp' => 'required|digits:4'
            ]);

            $cliente = Cliente::where('email', $request->email)->first();

            if (!$cliente || !$cliente->otp_code || now()->gt($cliente->otp_expires_at)) {
                return response()->json([
                    'code' => 'CLI-006',
                    'status' => 'error',
                    'message' => 'El código ha expirado o no es válido.',
                    'verified' => false,
                    'can_resend' => true
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->otp !== $cliente->otp_code) {
                return response()->json([
                    'code' => 'CLI-007',
                    'status' => 'error',
                    'message' => 'Código incorrecto.',
                    'verified' => false
                ], Response::HTTP_UNAUTHORIZED);
            }

            $cliente->update([
                'otp_code' => null,
                'otp_expires_at' => null,
                'email_verificado' => true
            ]);

            $cliente->tokens()->delete(); // Invalida tokens previos

            $token = $cliente->createToken('auth_token')->plainTextToken;

            return response()->json([
                'code' => 'CLI-008',
                'status' => 'success',
                'message' => 'Inicio de sesión exitoso.',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'cliente' => [
                        'nombre' => $cliente->nombre,
                        'apellido' => $cliente->apellido,
                    ]
                ]
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 'CLI-001',
                'status' => 'error',
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Error al verificar OTP: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error en la verificación del código.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Cierra la sesión del cliente eliminando el token actual.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();

            return response()->json([
                'code' => 'CLI-009',
                'status' => 'success',
                'message' => 'Sesión cerrada correctamente.'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error al cerrar sesión: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al cerrar la sesión.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Genera y envía un código OTP de 4 dígitos a un cliente.
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
     * Envía un correo con el código OTP al cliente.
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
                'from' => env('RESEND_FROM', 'MesaFacil <no-reply@resend.dev>'),
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

    /**
     * Listar todos los clientes con dirección.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $clientes = Cliente::with('direccion')->get();

            return response()->json([
                'code' => 'CLI-010',
                'status' => 'success',
                'data' => $clientes
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al obtener clientes',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar cliente autenticado por id.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $cliente = $request->user();

            if ($cliente->id != $id) {
                return response()->json([
                    'code' => 'CLI-011',
                    'status' => 'error',
                    'message' => 'Acceso no autorizado.'
                ], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                'code' => 'CLI-012',
                'status' => 'success',
                'data' => $cliente
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al obtener cliente',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza datos del cliente autenticado.
     *
     * @param ClienteRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ClienteRequest $request, $id)
    {
        $cliente = $request->user();

        // Validar que el cliente esté editando su propio perfil
        if ($cliente->id != $id) {
            return response()->json([
                'code' => 'CLI-013',
                'status' => 'error',
                'message' => 'Acceso no autorizado.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Verificar permiso explícito
        if (!$cliente->can('appmovil.perfil.update')) {
            return response()->json([
                'code' => 'CLI-403',
                'status' => 'error',
                'message' => 'No tienes permiso para actualizar tu perfil.'
            ], Response::HTTP_FORBIDDEN);
        }

        $cliente->update($request->validated());

        return response()->json([
            'code' => 'CLI-014',
            'status' => 'success',
            'message' => 'Cliente actualizado exitosamente.',
            'data' => $cliente
        ], Response::HTTP_OK);
    }


    /**
     * Elimina cliente autenticado.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $cliente = $request->user();

            if ($cliente->id != $id) {
                return response()->json([
                    'code' => 'CLI-015',
                    'status' => 'error',
                    'message' => 'Acceso no autorizado.'
                ], Response::HTTP_FORBIDDEN);
            }

            $cliente->delete();

            return response()->json([
                'code' => 'CLI-016',
                'status' => 'success',
                'message' => 'Cuenta eliminada.'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al eliminar cuenta',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
