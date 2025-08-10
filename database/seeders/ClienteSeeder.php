<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;
use App\Models\Direccion;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear la dirección primero
        $direccion = Direccion::create([
            'calle_linea_1' => 'Av. Siempre Viva 123',
            'calle_linea_2' => 'Depto 4B',
            'ciudad' => 'Springfield',
            'estado_provincia' => 'Estado Ejemplo',
            'codigo_postal' => '12345',
        ]);

        // Crear el cliente con direccion_id
        Cliente::create([
            'email' => 'guillermoescobar128@gmail.com',
            'nombre' => 'Luis Guillermo',
            'apellido' => 'Escobar',
            'direccion_id' => $direccion->id, // asignar id dirección creada
            'contrasena_hash' => Hash::make('secret123'),
            'telefono' => '5551234567',
            'email_verificado' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified_at' => now(),
        ]);
    }
}
