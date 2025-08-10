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
        Schema::create('oauth_authorization_codes', function (Blueprint $table) {
            $table->id(); // Un ID simple para la fila

            // Conexión con la tabla de clientes (oauth_clients)
            $table->foreignId('cliente_id')->nullable()->constrained('oauth_clients')->onDelete('cascade');
            
            // Conexión con la tabla de usuarios
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('cascade');
            
            // ¡LA COLUMNA QUE FALTABA!
            $table->string('code', 100)->unique(); // El código de autorización real

            $table->text('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_authorization_codes');
    }
};