<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class UsuarioController extends Controller
{
    /**
     * Registrar un nuevo usuario y asignarle un rol.
     */
    public function store(UsuarioRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $usuarioExistente = Usuario::where('email', $validatedData['email'])
                                    ->orWhere('usuario', $validatedData['usuario'])
                                    ->first();

            if ($usuarioExistente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El usuario o correo ya están registrados.',
                ], 422);
            }

            $role = Role::findById($validatedData['role_id'], 'api');

            if (!$role) {
                throw new Exception('El rol especificado no existe para el guard api.');
            }

            $usuario = Usuario::create([
                'usuario' => $validatedData['usuario'],
                'email' => $validatedData['email'],
                'contrasena_hash' => Hash::make($validatedData['contrasena']),
                'empleado_id' => $validatedData['empleado_id'] ?? null,
                'esta_activo' => $validatedData['esta_activo'] ?? true,
                'email_verificado' => true,
            ]);

            $usuario->assignRole($role);

            return response()->json([
                'status' => 'success',
                'message' => 'Usuario registrado correctamente.',
                'data' => $usuario->load('roles')
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno al registrar usuario',
            ], 500);
        }
    }


    /**
     * Actualizar un usuario existente.
     *
     * @param UsuarioRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UsuarioRequest $request, int $id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            $data = $request->validated();

            // 1. Obtén el ID del rol de los datos validados
            $newRoleId = $data['role_id'] ?? null;
            unset($data['role_id']); // Quita el role_id de los datos para no actualizar la columna directamente

            if (isset($data['contrasena'])) {
                $data['contrasena_hash'] = Hash::make($data['contrasena']);
                unset($data['contrasena']);
            }

            // 2. Actualiza los demás campos del usuario en la base de datos
            $usuario->update($data);

            // 3. ¡Esta es la parte clave! Sincroniza el nuevo rol con el usuario.
            if ($newRoleId) {
                $newRole = \Spatie\Permission\Models\Role::findById($newRoleId, 'api');
                $usuario->syncRoles([$newRole]);
            }

            return response()->json([
                'code' => 'USR-004',
                'status' => 'success',
                'message' => 'Usuario actualizado correctamente.',
                'data' => $usuario
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 'USR-404',
                'status' => 'error',
                'message' => 'Usuario no encontrado.'
            ], 404);
        } catch (\Exception $e) { // Usar \Exception para evitar conflictos
            Log::error('Error al actualizar usuario: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error interno al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reenviar código OTP al usuario.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOTP(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'nullable|email',
            'usuario' => 'nullable|string',
        ]);

        if (!$request->filled('email') && !$request->filled('usuario')) {
            return response()->json([
                'code' => 'VAL-002',
                'status' => 'error',
                'message' => 'Debes proporcionar un email o nombre de usuario.'
            ], 422);
        }

        $usuario = Usuario::when($request->filled('email'), fn($q) => $q->where('email', $request->email))
            ->when($request->filled('usuario'), fn($q) => $q->where('usuario', $request->usuario))
            ->first();

        if (!$usuario) {
            return response()->json([
                'code' => 'USR-404',
                'status' => 'error',
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        if ($usuario->email_verificado) {
            return response()->json([
                'code' => 'USR-005',
                'status' => 'error',
                'message' => 'El correo ya fue verificado.',
                'verified' => true
            ], 422);
        }

        try {
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $usuario->update([
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(10)
            ]);

            $this->sendOTPEmail($usuario->email, $otp, $usuario->usuario);

            return response()->json([
                'code' => 'USR-006',
                'status' => 'success',
                'message' => 'Nuevo código OTP enviado al correo electrónico.',
                'otp_expires_at' => $usuario->otp_expires_at->toDateTimeString()
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al reenviar OTP: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error interno al reenviar OTP.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar código OTP enviado al usuario.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOTP(Request $request): \Illuminate\Http\JsonResponse
    {
        dd($request->all());
        try {
            // 1. Validamos esperando 'otp_code' para ser consistentes con la BD
            $validated = $request->validate([
                'email' => 'required|email|exists:usuarios,email',
                'otp_code' => 'required|string|digits:4',
            ]);

            // 2. Buscamos al usuario
            $usuario = Usuario::where('email', $validated['email'])->firstOrFail();

            // 3. Verificamos que el código no haya expirado
            if (!$usuario->otp_expires_at || Carbon::now()->gt($usuario->otp_expires_at)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El código OTP ha expirado.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // 4. Verificamos que el código sea correcto
            if ($usuario->otp_code !== $validated['otp_code']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El código OTP es incorrecto.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // 5. ¡Éxito! Limpiamos los campos de OTP y marcamos como verificado
            $usuario->forceFill([
                'otp_code' => null,
                'otp_expires_at' => null,
                'otp_verified_at' => Carbon::now(),
                'email_verificado' => true,
            ])->save();

            // ¡LÍNEA DE DEPURACIÓN!
            // Si vemos este mensaje, sabemos que toda la lógica de verificación fue un éxito.
            dd('¡Verificación Exitosa! El siguiente paso es la redirección de OAuth2.');

            // 6. Devolvemos una respuesta de éxito clara
            return response()->json([
                'status' => 'success',
                'message' => 'Verificación exitosa. Procediendo con la autorización.',
                'verified' => true
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos de validación inválidos.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error("Error al verificar OTP: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado durante la verificación.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cerrar sesión (revocar token actual).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'code' => 'USR-010',
                'status' => 'success',
                'message' => 'Sesión cerrada correctamente.'
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al cerrar sesión: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al cerrar la sesión.'
            ], 500);
        }
    }

    /**
     * Lista todos los usuarios con su rol de Spatie.
     */
    public function index(): JsonResponse
    {
        try {
            // 1. Usamos 'with('roles')' para cargar la relación que nos da el paquete Spatie.
            // Esto es más eficiente que cargar el rol para cada usuario individualmente.
            $usuarios = Usuario::with('roles')->get()->map(function($user) {
                // 2. Obtenemos el primer rol del usuario. Un usuario podría tener varios,
                // pero para la tabla principal mostramos el más importante.
                $rol = $user->roles->first();
                
                // 3. Construimos la respuesta para cada usuario con el formato que el frontend espera.
                return [
                    'id' => $user->id,
                    'usuario' => $user->usuario,
                    'email' => $user->email,
                    'role_id' => $rol ? $rol->id : null,
                    'esta_activo' => $user->esta_activo,
                    'rol' => $rol ? $rol->name : 'Sin rol', // Spatie usa 'name' para el nombre del rol.
                ];
            });

            return response()->json([
                'code' => 'USR-011',
                'status' => 'success',
                'data' => $usuarios
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener usuarios: ' . $e->getMessage());
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al obtener la lista de usuarios.',
            ], 500);
        }
    }

    /**
     * Muestra un usuario específico con su rol de Spatie.
     */
    public function show(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $usuario = Usuario::with('roles')->findOrFail($id);
            $rol = $usuario->roles->first();

            return response()->json([
                'code' => 'USR-012',
                'status' => 'success',
                'data' => [
                    'id' => $usuario->id,
                    'usuario' => $usuario->usuario,
                    'email' => $usuario->email,
                    'role_id' => $rol ? $rol->id : null,
                    'esta_activo' => $usuario->esta_activo,
                    'rol' => $rol ? $rol->name : 'Sin rol',
                ]
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([/* ... manejo de error ... */], 404);
        } catch (Exception $e) {
            Log::error('Error al obtener usuario: ' . $e->getMessage());
            return response()->json([/* ... manejo de error ... */], 500);
        }
    }

    /**
     * Devuelve los datos y permisos del usuario autenticado.
     */
    public function profile(Request $request)
    {
        $usuarioAutenticado = Auth::user();

        if (!$usuarioAutenticado) {
            return response()->json(['status' => 'error', 'message' => 'Usuario no autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $rol = $usuarioAutenticado->roles->first();
        // ¡LA MAGIA! Obtenemos todos los nombres de los permisos del usuario.
        $permisos = $usuarioAutenticado->getAllPermissions()->pluck('name');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $usuarioAutenticado->id,
                'usuario' => $usuarioAutenticado->usuario,
                'email' => $usuarioAutenticado->email,
                'role_id' => $rol ? $rol->id : null,
                'esta_activo' => $usuarioAutenticado->esta_activo,
                'rol' => $rol ? $rol->name : 'Sin rol',
                'permisos' => $permisos, // <-- Añadimos los permisos a la respuesta
            ],
            'code' => Response::HTTP_OK
        ]);
    }

    /**
     * Eliminar usuario (soft delete).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            $usuario->delete();

            return response()->json([
                'code' => 'USR-013',
                'status' => 'success',
                'message' => 'Usuario eliminado correctamente.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 'USR-404',
                'status' => 'error',
                'message' => 'Usuario no encontrado.'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage());

            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al eliminar usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar correo con código OTP al usuario.
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

            $response = \Resend\Laravel\Facades\Resend::emails()->send([
                'from' => env('RESEND_FROM', 'MesaFacil <no-reply@resend.dev>'),
                'to' => [$email],
                'subject' => 'Tu código de verificación',
                'html' => $html,
            ]);

            Log::info('Correo OTP enviado a usuario', [
                'to' => $email,
                'otp' => $otp,
                'resend_id' => $response['id'] ?? null
            ]);
        } catch (Exception $e) {
            Log::error("Error enviando OTP al usuario: " . $e->getMessage());
        }
    }
}
