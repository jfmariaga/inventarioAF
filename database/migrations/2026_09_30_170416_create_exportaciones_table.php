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
        Schema::create('exportaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitado_por')->constrained('users')->cascadeOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('centro_costos_id')->nullable()->constrained('centros_costos')->nullOnDelete();
            $table->enum('estado', ['en_proceso', 'lista', 'fallida'])->default('en_proceso');
            $table->string('archivo_path', 255)->nullable();
            $table->timestamp('generado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exportaciones');
    }
};
