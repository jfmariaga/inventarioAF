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
        Schema::create('novedades_activos', function (Blueprint $table) {
            $table->id();
            $table->string('denominacion', 255);
            $table->string('numero_activo', 30)->nullable();
            $table->string('placa', 30)->nullable();
            $table->string('ubicacion_original', 100);
            $table->foreignId('centro_costos_id')->nullable()->constrained('centros_costos')->nullOnDelete();
            $table->date('fecha_capitalizacion')->nullable();
            $table->enum('estado', ['pendiente', 'completada'])->default('pendiente');
            $table->foreignId('activo_id')->nullable()->constrained('activos')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completada_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('novedades_activos');
    }
};
