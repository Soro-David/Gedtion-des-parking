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
            $table->string('label')->nullable(); // ex: "Première demi-heure", "Au-delà de 2h"
            $table->unsignedInteger('from_minutes')->default(0); // début de l'intervalle (en minutes)
            $table->unsignedInteger('to_minutes')->nullable();   // fin de l'intervalle (null = pas de limite)
            $table->decimal('amount', 10, 2);                    // montant fixe pour cet intervalle
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_rates');
    }
};
