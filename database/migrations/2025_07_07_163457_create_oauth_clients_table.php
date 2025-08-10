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
        Schema::create('oauth_clients', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Nombre app cliente (Ej: Web Admin)
        $table->string('client_id')->unique(); // identificador público
        $table->string('client_secret'); // secreto de cliente (puede encriptarse)
        $table->string('redirect_uri'); // a dónde regresar con el token
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};
