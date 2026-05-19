<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_rates', function (Blueprint $table) {
            $table->dropColumn(['from_minutes', 'to_minutes']);
            $table->time('from_time')->nullable()->after('label');
            $table->time('to_time')->nullable()->after('from_time');
        });
    }

    public function down(): void
    {
        Schema::table('parking_rates', function (Blueprint $table) {
            $table->dropColumn(['from_time', 'to_time']);
            $table->unsignedInteger('from_minutes')->default(0)->after('label');
            $table->unsignedInteger('to_minutes')->nullable()->after('from_minutes');
        });
    }
};
