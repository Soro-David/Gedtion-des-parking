<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->foreignId('reversement_id')
                ->nullable()
                ->constrained('reversements')
                ->onDelete('set null')
                ->after('closed_by');
        });
    }

    public function down(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversement_id');
        });
    }
};
