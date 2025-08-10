<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ClientePermisosSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesConPermisos = [
            'SUPERADMIN' => [
                'usuarios.view_all',
                'usuarios.view',
                'usuarios.create',
                'usuarios.update',
                'usuarios.delete',

                'empleados.view_all',
                'empleados.view',
                'empleados.create',
                'empleados.update',
                'empleados.delete',

                'roles.view_all',
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',

                'productos.view_all',
                'productos.view',
                'productos.create',
                'productos.update',
                'productos.delete',
                
                'sucursales.view_all',
                'sucursales.view',
                'sucursales.create',
                'sucursales.update',
                'sucursales.delete',
                
                'categorias.view_all',
                'categorias.view',
                'categorias.create',
                'categorias.update',
                'categorias.delete',
                
                'pedidos.view_all',
                'pedidos.view',
                'pedidos.create',
                'pedidos.update_estado',
            ],
            'ADMIN_SUC' => [
                'usuarios.view_all',
                'usuarios.view',
                'usuarios.create',
                'usuarios.update',
                'usuarios.delete',

                'empleados.view_all',
                'empleados.view',
                'empleados.create',
                'empleados.update',
                'empleados.delete',

                'roles.view_all',
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',

                'productos.view_all',
                'productos.view',
                'productos.create',
                'productos.update',
                'productos.delete',
                
                'sucursales.view_all',
                'sucursales.view',
                'sucursales.create',
                'sucursales.update',
                'sucursales.delete',
                
                'categorias.view_all',
                'categorias.view',
                'categorias.create',
                'categorias.update',
                'categorias.delete',
                
                'pedidos.view_all',
                'pedidos.view',
                'pedidos.create',
                'pedidos.update_estado',
            ],
            'CAJERO' => [
                'productos.view_all',
                'productos.view',
                'productos.create',
                'productos.update',
                'productos.delete',
                
                'sucursales.view_all',
                'sucursales.view',
                'sucursales.create',
                'sucursales.update',
                'sucursales.delete',
                
                'categorias.view_all',
                'categorias.view',
                'categorias.create',
                'categorias.update',
                'categorias.delete',
                
                'pedidos.view_all',
                'pedidos.view',
                'pedidos.create',
                'pedidos.update_estado',
            ],
            'MESERO' => [
                // Este rol no necesita permisos en la API por ahora.
            ],
            'COCINERO' => [
                'pedidos.view_all',
                'pedidos.view',
                'pedidos.update_estado',
            ],
            'CLIENTE' => [
                'productos.view_all',
                'pedidos.create',
                'pedidos.view',
            ],
        ];

        foreach ($rolesConPermisos as $rolNombre => $permisos) {
            // 🔹 Todos los roles tendrán el guard 'api'
            $guard = 'api';
            
            $roleInstance = Role::firstOrCreate(['name' => $rolNombre, 'guard_name' => $guard]);
            
            foreach ($permisos as $permisoNombre) {
                Permission::firstOrCreate(['name' => $permisoNombre, 'guard_name' => $guard]);
            }
            
            $roleInstance->syncPermissions($permisos);
        }
    }
}
