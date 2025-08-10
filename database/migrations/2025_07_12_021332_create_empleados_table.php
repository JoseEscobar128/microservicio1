<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
          Schema::create('empleados', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('nombre', 40);
            $table->string('apellido_materno', 20);
            $table->string('apellido_paterno', 20);
            $table->string('telefono', 10)->nullable();
            $table->char('rfc', 13)->nullable();
            $table->char('curp', 18)->nullable();
            $table->char('nss', 11)->nullable();
            $table->date('fecha_contratacion');
            $table->enum('estatus', ['activo', 'baja'])->default('activo');
            $table->boolean('esta_activo')->default(true);
            $table->binary('huella')->nullable();
            $table->timestamps();
            $table->softDeletes();

     
            

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
           Schema::dropIfExists('empleados');
    }
};
