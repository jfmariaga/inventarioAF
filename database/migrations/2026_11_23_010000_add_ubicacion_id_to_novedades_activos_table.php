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
            $table->foreignId('ubicacion_id')->nullable()->after('ubicacion_original')->constrained('ubicaciones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('novedades_activos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ubicacion_id');
        });
    }
};
