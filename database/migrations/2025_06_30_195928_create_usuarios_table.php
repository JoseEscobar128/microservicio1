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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('usuario')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('contrasena_hash');
            $table->boolean('esta_activo')->default(true);
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->boolean('email_verificado')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // La línea de 'role_id' ha sido eliminada de aquí temporalmente.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};