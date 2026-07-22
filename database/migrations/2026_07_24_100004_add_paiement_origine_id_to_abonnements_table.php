<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Added in a separate migration because paiements_abonnement (which it
     * references) can only be created once abonnements already exists —
     * paiements_abonnement.abonnement_id is not nullable. Set once, at
     * creation, to the payment that originated the abonnement; never
     * reassigned by later renewal payments.
     */
    public function up(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->foreignId('paiement_origine_id')->nullable()->after('offre_id')->constrained('paiements_abonnement')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropForeign(['paiement_origine_id']);
            $table->dropColumn('paiement_origine_id');
        });
    }
};
