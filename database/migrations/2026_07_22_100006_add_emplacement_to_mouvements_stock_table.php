<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock movements move from a strict point_de_vente_id dependency to a
     * polymorphic emplacement (PointDeVente or Depot), and gain a
     * receptionne_at marker used by transfert_entree movements — they exist
     * from the moment a transfer is initiated but only count towards
     * available stock once received. Existing movements were always tied to
     * a point de vente, so the backfill maps point_de_vente_id straight onto
     * the new emplacement columns before the old column is dropped.
     */
    public function up(): void
    {
        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->string('emplacement_type')->nullable()->after('produit_id');
            $table->unsignedBigInteger('emplacement_id')->nullable()->after('emplacement_type');
            $table->timestamp('receptionne_at')->nullable()->after('quantite');
        });

        DB::table('mouvements_stock')->update([
            'emplacement_type' => 'App\\Models\\PointDeVente',
            'emplacement_id' => DB::raw('point_de_vente_id'),
        ]);

        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->dropForeign(['point_de_vente_id']);
            $table->dropColumn('point_de_vente_id');

            $table->index(['emplacement_type', 'emplacement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->dropIndex(['emplacement_type', 'emplacement_id']);
            $table->foreignId('point_de_vente_id')->nullable()->after('produit_id')->constrained('points_de_vente')->cascadeOnDelete();
        });

        DB::table('mouvements_stock')
            ->where('emplacement_type', 'App\\Models\\PointDeVente')
            ->update([
                'point_de_vente_id' => DB::raw('emplacement_id'),
            ]);

        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->dropColumn(['emplacement_type', 'emplacement_id', 'receptionne_at']);
        });
    }
};
