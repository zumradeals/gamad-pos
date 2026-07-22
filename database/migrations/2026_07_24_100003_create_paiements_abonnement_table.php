<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The unique constraint on reference_externe is the ultimate guarantee
     * of idempotence: the same confirmation from the payment provider can
     * never be recorded twice, even under a race condition.
     */
    public function up(): void
    {
        Schema::create('paiements_abonnement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonnement_id')->constrained('abonnements')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('reference_externe')->unique();
            $table->string('statut');
            $table->dateTime('recu_a');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements_abonnement');
    }
};
