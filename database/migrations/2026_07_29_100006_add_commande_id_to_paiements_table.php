<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Même raisonnement que pour creances : un paiement (acompte) peut
     * désormais aussi être rattaché à une commande plutôt qu'à une vente.
     */
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->foreignId('commande_id')->nullable()->after('vente_id')->constrained('commandes')->cascadeOnDelete();
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->unsignedBigInteger('vente_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->unsignedBigInteger('vente_id')->nullable(false)->change();
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->dropForeign(['commande_id']);
            $table->dropColumn('commande_id');
        });
    }
};
