<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * Clase de validación para solicitudes relacionadas con empleados.
 *
 * Esta clase maneja la validación tanto para la creación como para la actualización
 * de empleados, y devuelve respuestas personalizadas en caso de errores.
 */
class EmpleadoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @return bool Siempre retorna true. Agrega lógica personalizada si se requiere control de acceso.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación según el método HTTP.
     *
     * - `POST`: Reglas para crear un nuevo empleado.
     * - `PUT` / `PATCH`: Reglas para actualizar un empleado existente.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Reglas de validación.
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'nombre' => 'required|string|max:40',
                'apellido_materno' => 'required|string|max:20',
                'apellido_paterno' => 'required|string|max:20',
                'telefono' => 'nullable|regex:/^\d{10}$/',
                'rfc' => 'nullable|size:13|unique:empleados,rfc',
                'curp' => 'nullable|size:18|unique:empleados,curp',
                'nss' => 'nullable|size:11|unique:empleados,nss',
                'fecha_contratacion' => 'required|date|before_or_equal:today',
                'role_id' => 'required|exists:roles,id',
                'estatus' => 'in:activo,baja',
                'huella' => 'nullable|string',
            ];
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $id = $this->route('id');
            return [
                'nombre' => 'sometimes|required|string|max:100',
                'apellido' => 'sometimes|required|string|max:100',
                'telefono' => 'nullable|regex:/^\d{10}$/',
                'rfc' => 'sometimes|required|size:13|unique:empleados,rfc,' . $id,
                'curp' => 'nullable|size:18|unique:empleados,curp,' . $id,
                'nss' => 'nullable|size:11|unique:empleados,nss,' . $id,
                'fecha_contratacion' => 'sometimes|required|date|before_or_equal:today',
                'role_id' => 'sometimes|required|exists:roles,id',
                'estatus' => 'in:activo,baja',
            ];
        }

        return [];
    }

    /**
     * Mensajes personalizados para errores de validación.
     *
     * @return array<string, string> Mensajes de error personalizados por campo.
     */
    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'telefono.regex' => 'El teléfono debe tener 10 dígitos numéricos.',
            'rfc.size' => 'El RFC debe tener 13 caracteres.',
            'curp.size' => 'La CURP debe tener 18 caracteres.',
            'nss.size' => 'El NSS debe tener 11 caracteres.',
            'fecha_contratacion.required' => 'La fecha de contratación es obligatoria.',
            'fecha_contratacion.before_or_equal' => 'La fecha de contratación no puede ser futura.',
            'role_id.exists' => 'El rol especificado no existe.',
            'estatus.in' => 'El estatus debe ser "activo" o "baja".',
            'rfc.unique' => 'Ya existe un empleado con este RFC.',
            'curp.unique' => 'Ya existe un empleado con esta CURP.',
            'nss.unique' => 'Ya existe un empleado con este NSS.',
        ];
    }

    /**
     * Personaliza la respuesta cuando falla la validación.
     *
     * Lanza una excepción con una respuesta JSON estructurada.
     *
     * @param ValidatorContract $validator Validador con los errores.
     * @throws HttpResponseException Lanza excepción con respuesta JSON de error.
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
