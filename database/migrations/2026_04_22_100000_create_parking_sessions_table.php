<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->constrained('parkings')->onDelete('cascade');
            $table->string('license_plate');
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->string('vehicle_image')->nullable();
            $table->enum('status', ['occupied', 'released'])->default('occupied');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reversement_id')->nullable()->constrained('reversements')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_sessions');
    }
};
