<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->nullable()->constrained('parkings')->onDelete('cascade');
            $table->string('label')->nullable();
            $table->unsignedInteger('from_minutes')->default(0);
            $table->unsignedInteger('to_minutes')->nullable();
            $table->decimal('amount', 10, 2);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_rates');
    }
};
