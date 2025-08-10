<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Empleado;
use Carbon\Carbon;

class EmpleadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Este seeder ahora solo crea los registros de los empleados.
        // La asignación de roles se hará desde el panel de administración
        // al crear un 'Usuario' y vincularlo a uno of estos empleados.

        Empleado::create([
            'nombre' => 'Ana',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'López',
            'telefono' => '8711234501',
            'rfc' => 'GALO880101HM1',
            'curp' => 'GALO880101HDFLRA01',
            'nss' => '12345678902',
            'fecha_contratacion' => Carbon::parse('2023-05-20'),
            'estatus' => 'activo',
        ]);

        Empleado::create([
            'nombre' => 'Luis',
            'apellido_paterno' => 'Martínez',
            'apellido_materno' => 'Hernández',
            'telefono' => '8711234502',
            'rfc' => 'MAHL900202DF2',
            'curp' => 'MAHL900202HDFRÑA02',
            'nss' => '12345678903',
            'fecha_contratacion' => Carbon::parse('2024-01-10'),
            'estatus' => 'activo',
        ]);

        Empleado::create([
            'nombre' => 'Sofía',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'Pérez',
            'telefono' => '8711234503',
            'rfc' => 'RAPS920303GH3',
            'curp' => 'RAPS920303MDFLRA03',
            'nss' => '12345678904',
            'fecha_contratacion' => Carbon::parse('2023-11-05'),
            'estatus' => 'activo',
        ]);
        
        Empleado::create([
            'nombre' => 'Pedro',
            'apellido_paterno' => 'Jiménez',
            'apellido_materno' => 'Flores',
            'telefono' => '8711234504',
            'rfc' => 'JIFP850404KL4',
            'curp' => 'JIFP850404HDFLRA04',
            'nss' => '12345678905',
            'fecha_contratacion' => Carbon::parse('2022-08-15'),
            'estatus' => 'baja',
        ]);
    }
}