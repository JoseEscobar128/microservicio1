<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ClienteRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación según el método HTTP.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'nombre' => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
                'telefono' => 'nullable|regex:/^\d{10}$/',
                'direccion.calle' => 'required|string|max:30',         
                'direccion.colonia' => 'required|string|max:20',
                'direccion.ciudad' => 'required|string|max:20',
                'direccion.estado' => 'required|string|max:20',
                'direccion.codigo_postal' => 'required|string|size:5',
            ];
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $id = $this->route('id') ?? $this->user()?->id;

            return [
                'nombre' => 'sometimes|required|string|max:100',
                'apellido' => 'sometimes|required|string|max:100',
                'email' => 'sometimes|required|email|unique:clientes,email,' . $id,
                'password' => 'nullable|string|min:8|confirmed',
                'telefono' => 'nullable|regex:/^\d{10}$/',
            ];
        }

        return [];
    }

    /**
     * Mensajes personalizados para errores de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo debe tener un formato válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'telefono.regex' => 'El teléfono debe tener exactamente 10 dígitos.',

            'direccion.calle.required' => 'La calle es obligatoria.',
            'direccion.colonia.required' => 'La colonia es obligatoria.',
            'direccion.ciudad.required' => 'La ciudad es obligatoria.',
            'direccion.estado.required' => 'El estado es obligatorio.',
            'direccion.codigo_postal.required' => 'El código postal es obligatorio.',
            'direccion.codigo_postal.size' => 'El código postal debe tener 5 dígitos.',
        ];
    }

    /**
     * Personaliza la respuesta cuando falla la validación.
     *
     * @param ValidatorContract $validator
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
}
