<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable()->after('ended_at');
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn('amount');
        });
    }
};
