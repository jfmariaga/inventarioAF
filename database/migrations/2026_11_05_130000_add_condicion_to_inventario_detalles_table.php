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
        Schema::table('inventario_detalles', function (Blueprint $table) {
            $table->enum('condicion', ['uso', 'desuso'])->nullable()->after('ubicacion_id');
            $table->text('motivo_desuso')->nullable()->after('condicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_detalles', function (Blueprint $table) {
            $table->dropColumn(['condicion', 'motivo_desuso']);
        });
    }
};
