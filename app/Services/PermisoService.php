<?php

namespace App\Services;

use App\Models\Rol;

class PermisoService
{
    public static function getPermisosPorRol(Rol $rol): array
    {
        $esSuperadmin = $rol->nombre === 'SuperAdmin';
        $esAdminSuc = $rol->nombre === 'AdminSucursal';

        return [
            'usuarios' => [
                'crear' => $esSuperadmin,
                'leer' => $esSuperadmin || $esAdminSuc,
                'actualizar' => $esSuperadmin,
                'eliminar' => $esSuperadmin,
                'cambiar_estado' => $esSuperadmin
            ],
            'roles' => [
                'crear' => $esSuperadmin,
                'leer' => $esSuperadmin,
                'actualizar' => $esSuperadmin,
                'eliminar' => $esSuperadmin,
                'asignar' => $esSuperadmin
            ]
        ];
    }
}
