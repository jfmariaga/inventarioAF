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
        Schema::create('activos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_activo', 30)->unique();
            $table->string('denominacion', 255);
            $table->date('fecha_capitalizacion')->nullable();
            $table->string('placa', 30)->nullable();
            $table->string('ubicacion_original', 100)->nullable();
            $table->foreignId('centro_costos_id')->constrained('centros_costos')->restrictOnDelete();
            $table->enum('origen', ['excel', 'sobrante'])->default('excel');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activos');
    }
};
