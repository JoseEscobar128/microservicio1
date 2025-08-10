<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;

class RolController extends Controller
{
    /**
     * Muestra la lista de roles con sus permisos.
     *
     * @return JsonResponse
     */
    public function indexRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'code' => 'ROL-001',
            'status' => 'success',
            'message' => 'Lista de roles obtenida correctamente.',
            'data' => $roles
        ], Response::HTTP_OK);
    }

    /**
     * Muestra un rol específico con sus permisos.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showRole(int $id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return response()->json([
                'code' => 'ROL-404',
                'status' => 'error',
                'message' => 'Rol no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'code' => 'ROL-002',
            'status' => 'success',
            'message' => 'Rol encontrado correctamente.',
            'data' => $role
        ], Response::HTTP_OK);
    }

    /**
     * Crea un nuevo rol con permisos opcionales.
     *
     * @param StoreRolRequest $request
     * @return JsonResponse
     */
    public function storeRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'required|string', // o integer según tu validación
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 'ROL-VALID-001',
                'status' => 'error',
                'message' => 'Error de validación en los campos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Aquí asignas guard_name fijo
            $role = Role::create([
                'name' => $request->input('name'),
                'guard_name' => 'empleado', // siempre 'empleado'
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            return response()->json([
                'code' => 'ROL-003',
                'status' => 'success',
                'message' => 'Rol creado correctamente.',
                'data' => $role->load('permissions'),
            ], Response::HTTP_CREATED);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Error en la base de datos al crear rol', ['error' => $e->getMessage()]);

            // Validar si el error fue por duplicado (unique)
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'code' => 'ROL-VALID-002',
                    'status' => 'error',
                    'message' => 'El nombre del rol ya existe.',
                ], 422);
            }

            return response()->json([
                'code' => 'ROL-DB-001',
                'status' => 'error',
                'message' => 'Error en la base de datos al crear el rol.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error inesperado al crear rol', ['error' => $e->getMessage()]);

            return response()->json([
                'code' => 'ROL-500',
                'status' => 'error',
                'message' => 'Error inesperado al crear el rol. Intenta más tarde.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza un rol y sus permisos.
     *
     * @param UpdateRolRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'code' => 'ROL-404',
                'status' => 'error',
                'message' => 'Rol no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'permissions' => 'nullable|array',
            'permissions.*' => ['required', function ($attribute, $value, $fail) {
                if (!is_string($value) && !is_int($value)) {
                    $fail("El valor de $attribute debe ser un string o un número.");
                }
            }]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 'ROL-VALID-001',
                'status' => 'error',
                'message' => 'Error de validación en los campos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $existingPermissions = $role->permissions->pluck('name')->toArray(); // ya asignados
            $incomingPermissions = $request->input('permissions', []);

            // Convertimos todos a nombres para comparación (id o nombre)
            $resolvedPermissions = collect($incomingPermissions)->map(function ($perm) {
                if (is_numeric($perm)) {
                    $permObj = Permission::find($perm);
                } else {
                    $permObj = Permission::where('name', $perm)->first();
                }
                return $permObj ? $permObj->name : null;
            })->filter()->toArray();

            // Verificamos si alguno ya está asignado
            $duplicated = array_intersect($existingPermissions, $resolvedPermissions);

            if (!empty($duplicated)) {
                return response()->json([
                    'code' => 'ROL-VALID-002',
                    'status' => 'error',
                    'message' => 'El rol ya contiene uno o más de los permisos proporcionados.',
                    'duplicated_permissions' => array_values($duplicated),
                ], 422);
            }

            // Agregar los nuevos permisos
            $newPermissions = array_merge($existingPermissions, $resolvedPermissions);
            $role->syncPermissions($newPermissions);

            return response()->json([
                'code' => 'ROL-007',
                'status' => 'success',
                'message' => 'Permisos agregados correctamente al rol.',
                'data' => $role->load('permissions'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error agregando permisos al rol', ['error' => $e->getMessage()]);

            return response()->json([
                'code' => 'ROL-500',
                'status' => 'error',
                'message' => 'Error al agregar permisos al rol.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Elimina un permiso específico de un rol.
     *
     * @param int $id
     * @param int $permissionId
     * @return JsonResponse
     */
    public function removePermission(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permission' => 'required'
        ], [
            'permission.required' => 'El permiso es obligatorio.'
        ]);

        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'code' => 'ROL-005',
                'status' => 'error',
                'message' => 'Rol no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $input = $request->input('permission');

        $permission = Permission::where('id', $input)
            ->orWhere('name', $input)
            ->first();

        if (!$permission) {
            return response()->json([
                'code' => 'PER-005',
                'status' => 'error',
                'message' => 'Permiso no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            if (!$role->hasPermissionTo($permission)) {
                return response()->json([
                    'code' => 'ROL-409',
                    'status' => 'error',
                    'message' => 'El rol no tiene asignado este permiso.'
                ], Response::HTTP_CONFLICT);
            }
        } catch (GuardDoesNotMatch $e) {
            return response()->json([
                'code' => 'ROL-015',
                'status' => 'error',
                'message' => "El guard del rol ({$role->guard_name}) no coincide con el guard del permiso ({$permission->guard_name})."
            ], Response::HTTP_CONFLICT);
        }

        try {
            $role->revokePermissionTo($permission);

            return response()->json([
                'code' => 'ROL-010',
                'status' => 'success',
                'message' => 'Permisos eliminados correctamente.',
                'data' => $role->load('permissions')
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json([
                'code' => 'ROL-011',
                'status' => 'error',
                'message' => 'Error al eliminar permisos. Intenta más tarde.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Elimina un rol.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroyRole(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'code' => 'ROL-404',
                'status' => 'error',
                'message' => 'Rol no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $role->delete();

            return response()->json([
                'code' => 'ROL-006',
                'status' => 'success',
                'message' => 'Rol eliminado correctamente.'
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json([
                'code' => 'ROL-500',
                'status' => 'error',
                'message' => 'Error al eliminar el rol.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ==== Permisos ====

    /**
     * Lista todos los permisos disponibles.
     *
     * @return JsonResponse
     */
    public function indexPermisos(): JsonResponse
    {
        $permisos = Permission::all();

        return response()->json([
            'code' => 'PER-010',
            'status' => 'success',
            'message' => 'Lista de permisos obtenida correctamente',
            'data' => $permisos,
        ], Response::HTTP_OK);
    }

    /**
     * Crea un nuevo permiso.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storePermiso(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:permissions,name',
            ]);

            $permiso = Permission::create([
                'name' => $request->input('name'),
                'guard_name' => 'empleado' // Fijamos el guard
            ]);

            return response()->json([
                'code' => 'PER-002',
                'status' => 'success',
                'message' => 'Permiso creado correctamente.',
                'data' => $permiso
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 'PER-001',
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            Log::error('Error en la base de datos al crear permiso', ['error' => $e->getMessage()]);

            return response()->json([
                'code' => 'PER-003',
                'status' => 'error',
                'message' => 'Error al crear el permiso. Intenta más tarde.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            Log::error('Error inesperado al crear permiso', ['error' => $e->getMessage()]);

            return response()->json([
                'code' => 'PER-003',
                'status' => 'error',
                'message' => 'Error al crear el permiso. Intenta más tarde.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Muestra un permiso específico por ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showPermiso(int $id): JsonResponse
    {
        try {
            $permiso = Permission::findOrFail($id);

            return response()->json([
                'code' => 'PER-004',
                'status' => 'success',
                'message' => 'Permiso obtenido correctamente',
                'data' => $permiso
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 'PER-005',
                'status' => 'error',
                'message' => 'Permiso no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }
    }


    /**
     * Actualiza un permiso existente.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updatePermiso(Request $request, int $id): JsonResponse
    {
        try {
            $permiso = Permission::findOrFail($id);

            $request->validate([
                'name' => 'required|string|unique:permissions,name,' . $permiso->id,
            ]);

            $permiso->update([
                'name' => $request->input('name'),
                'guard_name' => 'empleado', // mantenemos el guard fijo
            ]);

            return response()->json([
                'code' => 'PER-006',
                'status' => 'success',
                'message' => 'Permiso actualizado correctamente.',
                'data' => $permiso
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 'PER-001',
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 'PER-005',
                'status' => 'error',
                'message' => 'Permiso no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('Error inesperado al actualizar permiso', ['error' => $e->getMessage()]);

            return response()->json([
                'code' => 'PER-007',
                'status' => 'error',
                'message' => 'Error al actualizar el permiso. Intenta más tarde.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Elimina un permiso por ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroyPermiso(int $id): JsonResponse
    {
        try {
            $permiso = Permission::findOrFail($id);
            $permiso->delete();

            return response()->json([
                'code' => 'PER-008',
                'status' => 'success',
                'message' => 'Permiso eliminado correctamente.'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 'PER-005',
                'status' => 'error',
                'message' => 'Permiso no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('Error inesperado al eliminar permiso', ['error' => $e->getMessage()]);

            return response()->json([
                'code' => 'PER-009',
                'status' => 'error',
                'message' => 'Error al eliminar el permiso. Intenta más tarde.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
