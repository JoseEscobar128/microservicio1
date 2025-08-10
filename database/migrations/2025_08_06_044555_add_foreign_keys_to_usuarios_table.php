<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Esta migración ahora solo se encarga de la llave foránea de empleados.
            $table->unsignedMediumInteger('empleado_id')
                  ->nullable()
                  ->after('contrasena_hash');
            
            $table->foreign('empleado_id')
                  ->references('id')
                  ->on('empleados')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['empleado_id']);
            $table->dropColumn('empleado_id');
        });
    }
};