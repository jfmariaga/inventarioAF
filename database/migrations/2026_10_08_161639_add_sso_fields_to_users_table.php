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
        Schema::table('users', function (Blueprint $table) {
            $table->string('azure_oid', 64)->nullable()->unique()->after('email');
            $table->string('sso_tenant', 20)->nullable()->after('azure_oid');
            $table->timestamp('last_login_at')->nullable()->after('sso_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['azure_oid', 'sso_tenant', 'last_login_at']);
        });
    }
};
