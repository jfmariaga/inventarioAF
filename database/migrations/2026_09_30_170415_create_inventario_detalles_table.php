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
        Schema::create('inventario_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_id')->constrained('inventarios')->cascadeOnDelete();
            $table->foreignId('activo_id')->constrained('activos')->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'verificado', 'no_encontrado', 'sobrante'])->default('pendiente');
            $table->string('ubicacion', 150)->nullable();
            $table->text('observacion')->nullable();
            $table->string('foto_equipo_path', 255)->nullable();
            $table->string('foto_placa_path', 255)->nullable();
            $table->string('foto_equipo_path_anterior', 255)->nullable();
            $table->string('foto_placa_path_anterior', 255)->nullable();
            $table->timestamp('fotos_reemplazadas_en')->nullable();
            $table->foreignId('reservado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reservado_en')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inventario_id', 'activo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_detalles');
    }
};
