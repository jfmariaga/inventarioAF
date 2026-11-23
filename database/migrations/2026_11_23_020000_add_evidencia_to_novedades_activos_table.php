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
        Schema::table('novedades_activos', function (Blueprint $table) {
            $table->enum('condicion', ['uso', 'desuso'])->nullable()->after('ubicacion_id');
            $table->text('motivo_desuso')->nullable()->after('condicion');
            $table->string('foto_equipo_path', 255)->nullable()->after('motivo_desuso');
            $table->string('foto_placa_path', 255)->nullable()->after('foto_equipo_path');
            $table->text('observacion')->nullable()->after('foto_placa_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('novedades_activos', function (Blueprint $table) {
            $table->dropColumn(['condicion', 'motivo_desuso', 'foto_equipo_path', 'foto_placa_path', 'observacion']);
        });
    }
};
