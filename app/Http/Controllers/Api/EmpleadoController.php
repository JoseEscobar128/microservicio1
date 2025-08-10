<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Http\Requests\EmpleadoRequest;

class EmpleadoController extends Controller
{
    /**
     * Registrar un nuevo empleado.
     *
     * Este método valida los datos recibidos, verifica si el empleado ya existe (incluso si fue eliminado lógicamente),
     * y lo crea o restaura según corresponda.
     *
     * @param EmpleadoRequest $request Datos validados del empleado.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el estado del registro.
     */
    public function store(EmpleadoRequest $request)
    {
        try {
            $existente = Empleado::withTrashed()
                ->where(function ($query) use ($request) {
                    if ($request->rfc) $query->orWhere('rfc', $request->rfc);
                    if ($request->curp) $query->orWhere('curp', $request->curp);
                    if ($request->nss) $query->orWhere('nss', $request->nss);
                })
                ->first();

            if ($existente) {
                if ($existente->trashed()) {
                    $existente->restore();
                    $existente->update($request->validated());

                    return response()->json([
                        'code' => 'EMP-003',
                        'status' => 'success',
                        'message' => 'Empleado restaurado y actualizado correctamente',
                        'data' => $existente
                    ], Response::HTTP_OK);
                }

                return response()->json([
                    'code' => 'EMP-002',
                    'status' => 'error',
                    'message' => 'Ya existe un empleado con RFC, CURP o NSS registrado.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $datos = $request->validated();

            // Si viene en base64, conviértelo a binario
            if (!empty($datos['huella'])) {
                $datos['huella'] = base64_decode($datos['huella']);
            }

            $empleado = Empleado::create($datos);

            // Se eliminó la lógica de asignación de rol con Spatie, ya que el rol se asigna
            // a la cuenta de usuario, no al registro del empleado.

            return response()->json([
                'code' => 'EMP-001',
                'status' => 'success',
                'message' => 'Empleado registrado correctamente',
                'data' => tap($empleado, function ($e) {
                    if (!is_null($e->huella)) {
                        $e->huella = base64_encode($e->huella);
                    }
                })
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al registrar empleado',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Listar todos los empleados.
     *
     * Recupera todos los empleados registrados en el sistema (sin incluir los eliminados lógicamente).
     *
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con la lista de empleados.
     */
    public function index()
    {
        try {
            $empleados = Empleado::all();

            // Convertir huella binaria a base64 antes de enviarla
            foreach ($empleados as $empleado) {
                if (!is_null($empleado->huella)) {
                    $empleado->huella = base64_encode($empleado->huella);
                }
            }

            return response()->json([
                'code' => 'EMP-004',
                'status' => 'success',
                'data' => $empleados
            ]);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al obtener empleados',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar un empleado por ID.
     *
     * Obtiene los datos de un empleado específico por su ID.
     *
     * @param int $id ID del empleado.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con los datos del empleado.
     */
    public function show($id)
    {
        try {
            // Se eliminó la relación 'rol' del 'with' porque el rol pertenece al usuario, no al empleado.
            $empleado = Empleado::with('usuario')->findOrFail($id);

            if (!is_null($empleado->huella)) {
                $empleado->huella = base64_encode($empleado->huella);
            }

            return response()->json([
                'code' => 'EMP-005',
                'status' => 'success',
                'data' => $empleado
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 'EMP-404',
                'status' => 'error',
                'message' => 'Empleado no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al obtener empleado',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar datos de un empleado.
     *
     * Actualiza los datos del empleado especificado con los valores proporcionados en la solicitud.
     *
     * @param EmpleadoRequest $request Datos validados a actualizar.
     * @param int $id ID del empleado a actualizar.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el estado de la operación.
     */
    public function update(EmpleadoRequest $request, $id)
    {
        try {
            $empleado = Empleado::findOrFail($id);
            $datos = $request->validated();
            
            // Se eliminó toda la lógica relacionada con 'role_id' y Spatie.
            
            if (!empty($datos['huella'])) {
                $datos['huella'] = base64_decode($datos['huella']);
            }

            $empleado->update($datos);

            return response()->json([
                'code' => 'EMP-006',
                'status' => 'success',
                'message' => 'Empleado actualizado correctamente',
                'data' => $empleado
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 'EMP-404',
                'status' => 'error',
                'message' => 'Empleado no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al actualizar empleado',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar un empleado (Soft Delete).
     *
     * Elimina lógicamente al empleado y, si existe un usuario asociado, también lo elimina lógicamente.
     *
     * @param int $id ID del empleado a eliminar.
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la operación.
     */
    public function destroy($id)
    {
        try {
            $empleado = Empleado::findOrFail($id);

            // Si hay usuario asociado, eliminarlo primero (soft delete)
            if ($empleado->usuario) {
                $empleado->usuario->delete();
            }

            // Luego eliminar el empleado (soft delete)
            $empleado->delete();

            return response()->json([
                'code' => 'EMP-007',
                'status' => 'success',
                'message' => 'Empleado y usuario asociado eliminados correctamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 'EMP-404',
                'status' => 'error',
                'message' => 'Empleado no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'code' => 'SRV-500',
                'status' => 'error',
                'message' => 'Error al eliminar empleado',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}