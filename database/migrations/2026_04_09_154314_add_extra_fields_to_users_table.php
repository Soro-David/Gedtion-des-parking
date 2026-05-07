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
            $table->string('unique_code')->unique()->nullable()->after('id');
            $table->string('first_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_name')->nullable();
            // We keep 'name' as the last name for compatibility, or we could rename it.
            // Let's just use 'name' as 'Nom' and 'first_name' as 'Prénoms'.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'unique_code',
                'first_name',
                'phone',
                'address',
                'emergency_contact',
                'emergency_relationship',
                'emergency_name',
            ]);
        });
    }
};
