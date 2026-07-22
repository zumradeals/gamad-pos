<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A produit no longer belongs to a single point de vente — it belongs to
     * the entreprise, and carries a distinct stock per emplacement (see the
     * mouvements_stock migration that follows this one). Existing produits
     * are re-attached to the entreprise of the point de vente they were
     * created in; the point_de_vente_id column becomes redundant and is
     * dropped once every row has been backfilled.
     */
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable()->after('id')->constrained('entreprises')->restrictOnDelete();
        });

        DB::table('produits')->update([
            'entreprise_id' => DB::raw(
                '(select points_de_vente.entreprise_id from points_de_vente where points_de_vente.id = produits.point_de_vente_id)'
            ),
        ]);

        Schema::table('produits', function (Blueprint $table) {
            $table->dropForeign(['point_de_vente_id']);
            $table->dropColumn('point_de_vente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->foreignId('point_de_vente_id')->nullable()->after('id')->constrained('points_de_vente')->cascadeOnDelete();
        });

        DB::table('produits')->update([
            'point_de_vente_id' => DB::raw(
                '(select points_de_vente.id from points_de_vente where points_de_vente.entreprise_id = produits.entreprise_id limit 1)'
            ),
        ]);

        Schema::table('produits', function (Blueprint $table) {
            $table->dropForeign(['entreprise_id']);
            $table->dropColumn('entreprise_id');
        });
    }
};
