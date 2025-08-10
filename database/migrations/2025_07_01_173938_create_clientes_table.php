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
    Schema::create('clientes', function (Blueprint $table) {
        $table->id();
        $table->string('email')->unique();
        $table->string('nombre', 100);
        $table->string('apellido', 100);
        $table->foreignId('direccion_id')->nullable()->constrained('direcciones');
        $table->string('contrasena_hash', 60);
        $table->string('google_id', 128)->nullable()->unique();
        $table->string('telefono', 20)->nullable();
        $table->string('otp_code', 4)->nullable();
        $table->timestamp('otp_expires_at')->nullable();
        $table->timestamp('otp_verified_at')->nullable();
        $table->boolean('email_verificado')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
