<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Empleado;

class SuperUsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear un empleado usando Eloquent
        $empleado = Empleado::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'General',
            'apellido_materno' => 'Del Sistema',
            'telefono' => '5550000000',
            'rfc' => 'ADM001000XXX',
            'curp' => 'ADM001000HDFXXX01',
            'nss' => '12345678901',
            'fecha_contratacion' => now(),
            'estatus' => 'activo',
            'role_id' => 1,
            'esta_activo' => true,
            'huella' => 'TWlTUjIyAAAEKikFBQUHCc7QAAAoK38AAAAAgzkekiqjAYUPlgAzAIIl5AGJAB8PegAQK4YOVwADASsPLirGAVUNZADmAfwlIwFeAGwNvgBMK30OzABNAbgPtyrdAZoPzAAYAJsl+wGXAJ0P/ADXKuMOrwBAAEgLJyrXAdkN7ADeAY8tJAELAe0L8wA4KvsOcQAKALsObyrQAfsPvADFAZQl3wH9AJYPPwB6KiAOBQHdAGcM9CpXAZsPVQD/AHwlcwEiAAMPxwE9Kp8OGQE0AG8KX6Uq7h/r3u7D+hMrTAN5hiuFcPnm2AL9xvkPAab2eVZIdDNfOg13f2td63f/9/tra4e3uQoCu/vf93rv7dQfDEoWZQbLCTI4pH99gp8H/I0dIJ7oh48D91YTfa6g++ftpvS6BIvUjgBTFWMPQ4TauHb6QX5bCqKHAzPWD6d2LQkr/a7TGw1qCZb5CAalrBeLRw1jBuobutcffy99hoArdtvUPIfiju8LHPY+Lyp+TQ47Ee6Lm4uGKwEHAADKXmEMBAD/yCIOZNP2a5CrvbMgOcQCcAkwAgBHAHoBBgT1ARZG/wMAKwAU1AgBpwEG/oXBOyABzwEXPcGG/gsqdAMAQEQ9BcFALAFuCYPCZtQAcycB/jVT/0wEWAArHRcwWQgAqhB+Y8GFCwB2IcUzxOpFwRQABDM1/cfVR8DB//xEBT76IAE4OPc3OIADBBs/cf8aAAqA3v5H/MA4/8D9BWVQTi0SABdU9JZXROo8/8BKCwBjUpSpwHlqCAC5nBf76sHBOwYA9ZweX9QEAf19Gv+GBgTRgyXAwEYUxeGAtmrAwYBxdL3BASrkjSTBTBrFAJ32NMA1wED+Oj77F2XBBAEBm+dhDyqSnoyXwcIFhQ0qkqSGjcHBrCMEsacWOMD/cAbYwdXBwf/BwcAEwsTpw8PCwcDCBf/F68EaAACp2vBgPB8uQUL/axrFAL/8/MD+VMD/8Cv56//BPsFqG8UBz/wuVf5PMP46PsTUwGjACgBvFfT5CMD+ahsABBHeTuo/wSr//v87wPvq/8DB/8JLywA4/+bAwf3A/Tv9+dX+wcAFADUdZFwpASHZYsEPxbLevW/EncBKWcMAkN+Nw58PALo6k8RNwsTBwMD/BfzHMhEKBOvBwKHAS9X6K8DAw/87wIMmEVoG8P8yO0xpLBFSCG1R/9EQIiPqc8Es/i2HwvtKDxG9EYxvsFjFcAURxRYJccsQuT2C/njCw/wHwPt8DBFmIvpEl/7E6kgTEPg1hqfAxOpdwW7Awf/AES4XVsMxDxDn+4NhQVd5wQUQeY99fCUR60iDwvy6wfvXknIDEABL/8MPOstPfcDAWATBSSYR5lF6ZEKXwAw63lp0REoD1ctvUMEBREIBAcUABDwBAQAAAhjFAAQqAQFFQg==',
        ]);

        // Asignar rol usando Spatie
        $empleado->assignRole('SUPERADMIN');

        // 2. Crear el usuario vinculado a ese empleado
        DB::table('usuarios')->insert([
            'usuario' => 'juanperez123',
            'email' => 'guillermoescobar128@gmail.com',
            'contrasena_hash' => Hash::make('Password123'),
            'empleado_id' => $empleado->id,
            'esta_activo' => true,
            'email_verificado' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified_at' => Carbon::now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
          DB::table('usuarios')->insert([
            'usuario' => 'juanperez1234',
            'email' => 'guillermo_escobar128@hotmail.com',
            'contrasena_hash' => Hash::make('Password123'),
            'empleado_id' => $empleado->id,
            'esta_activo' => true,
            'email_verificado' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified_at' => Carbon::now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);


DB::table('usuarios')->insert([
            'usuario' => 'corpus123',
            'email' => 'corpusj1493@gmail.com',
            'contrasena_hash' => Hash::make('Password123'),
            'empleado_id' => $empleado->id,
            'esta_activo' => true,
            'email_verificado' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified_at' => Carbon::now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}

