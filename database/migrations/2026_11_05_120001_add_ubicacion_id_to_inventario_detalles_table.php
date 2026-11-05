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
            $table->foreignId('ubicacion_id')->nullable()->after('ubicacion')
                ->constrained('ubicaciones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ubicacion_id');
        });
    }
};
