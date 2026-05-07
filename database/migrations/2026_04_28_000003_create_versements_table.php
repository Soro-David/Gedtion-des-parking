<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table principale des versements (initiés par admin/superviseur)
        Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');   // admin ou superviseur
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');   // agent ou caissier
            $table->decimal('collected_amount', 12, 2)->default(0); // montant encaissé depuis dernier versement
            $table->decimal('previous_debt', 12, 2)->default(0);    // dette cumulée des versements précédents
            $table->decimal('total_due', 12, 2)->default(0);        // collected_amount + previous_debt
            $table->decimal('paid_amount', 12, 2)->default(0);      // montant effectivement versé
            $table->decimal('remaining_debt', 12, 2)->default(0);   // total_due - paid_amount
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Lier les sessions de parking à un versement admin
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->foreignId('versement_id')
                ->nullable()
                ->constrained('versements')
                ->onDelete('set null')
                ->after('reversement_id');
        });
    }

    public function down(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('versement_id');
        });
        Schema::dropIfExists('versements');
    }
};
