<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('versements', function (Blueprint $table) {
            // Rendre agent_id nullable (pour les versements concernant un caissier)
            $table->foreignId('caissier_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('agent_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('versements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caissier_id');
            $table->foreignId('agent_id')->nullable(false)->change();
        });
    }
};
