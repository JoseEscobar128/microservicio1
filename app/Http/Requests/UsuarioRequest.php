<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use App\Models\Usuario;
use Illuminate\Validation\Rule;

/**
 * Clase para validar las solicitudes relacionadas con el modelo Usuario.
 *
 * Valida tanto la creación como la actualización, incluyendo reglas y mensajes personalizados.
 * Además, maneja la respuesta JSON en caso de errores de validación.
 */
class UsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @return bool Retorna true para permitir la validación (personalizar si es necesario).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para la solicitud.
     *
     * - Para método POST (crear): valida campos obligatorios y únicos.
     * - Para métodos PUT/PATCH (actualizar): valida campos opcionales, y la unicidad ignorando el registro actual.
     *
     * @return array<string, mixed> Arreglo con las reglas de validación.
     */


    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'usuario' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('usuarios')->whereNull('deleted_at'),
                ],
                'email' => [
                    'required',
                    'email',
                    Rule::unique('usuarios')->whereNull('deleted_at'),
                ],
                'contrasena' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
                ],
                'empleado_id' => 'required|exists:empleados,id',
                            // --- ¡LA LÍNEA QUE FALTABA! ---
                'role_id' => 'required|integer|exists:roles,id',
                'esta_activo' => 'sometimes|boolean',
            ];
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $id = $this->route('id');
            return [
                'usuario' => [
                    'sometimes',
                    'string',
                    'max:30',
                    Rule::unique('usuarios')->ignore($id)->whereNull('deleted_at'),
                ],
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('usuarios')->ignore($id)->whereNull('deleted_at'),
                ],
                'contrasena' => [
                    'nullable',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
                ],
                // --- AÑADIMOS LA REGLA PARA ACTUALIZAR ---
                'role_id' => 'sometimes|integer|exists:roles,id',
                'esta_activo' => 'sometimes|boolean',
            ];
        }

        return [];
    }


    /**
     * Mensajes personalizados para errores de validación.
     *
     * @return array<string, string> Arreglo con mensajes personalizados por regla y campo.
     */
    public function messages(): array
    {
        return [
            'usuario.required' => 'El campo usuario es obligatorio.',
            'usuario.string' => 'El usuario debe ser una cadena de texto.',
            'usuario.max' => 'El usuario no debe exceder 30 caracteres.',
            'usuario.unique' => 'El nombre de usuario ya está en uso.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'El correo electrónico ya está registrado.',

            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasena.regex' => 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número.',

            'empleado_id.required' => 'El campo empleado es obligatorio.',
            'empleado_id.exists' => 'El empleado no existe en el sistema.',

            // --- MENSAJES PARA EL NUEVO CAMPO ---
            'role_id.required' => 'El campo rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
        ];
    }

    /**
     * Respuesta JSON personalizada cuando la validación falla.
     *
     * Lanza una excepción con la respuesta estructurada para la API.
     *
     * @param ValidatorContract $validator El validador que contiene los errores.
     * @throws HttpResponseException Excepción que envía la respuesta JSON con errores.
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 'VAL-001',
            'status' => 'error',
            'message' => 'Datos inválidos',
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * Añade validaciones adicionales después de las reglas básicas.
     *
     * Verifica que un empleado no tenga ya un usuario asignado en la base de datos,
     * incluso si el usuario está eliminado lógicamente.
     *
     * @param \Illuminate\Validation\Validator $validator El validador para añadir errores personalizados.
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->isMethod('post') && $this->filled('empleado_id')) {
                $yaExiste = Usuario::whereNull('deleted_at') // solo usuarios activos
                    ->where('empleado_id', $this->empleado_id)
                    ->exists();

                if ($yaExiste) {
                    $validator->errors()->add('empleado_id', 'Este empleado ya tiene un usuario asignado.');
                }
            }
        });
    }
}
