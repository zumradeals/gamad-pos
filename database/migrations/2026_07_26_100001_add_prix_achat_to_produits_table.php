<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Historique des coûts simple : prix_achat est celui du dernier achat
     * validé pour ce produit, pas une moyenne pondérée. Choix explicite
     * (Chantier 11) — à revisiter si une valorisation de stock plus fine
     * est requise plus tard.
     */
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->decimal('prix_achat', 12, 2)->nullable()->after('prix_vente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('prix_achat');
        });
    }
};
