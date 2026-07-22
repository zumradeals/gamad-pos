<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Réutilisation explicite du mécanisme Créance/Versement du Chantier 4
     * pour l'acompte de commande (Chantier 14) — pas une variante
     * réinventée. Une créance naît soit d'une vente, soit d'une commande,
     * jamais des deux : vente_id devient nullable, commande_id nullable et
     * unique lui est ajouté en miroir.
     */
    public function up(): void
    {
        Schema::table('creances', function (Blueprint $table) {
            $table->foreignId('commande_id')->nullable()->unique()->after('vente_id')->constrained('commandes')->restrictOnDelete();
        });

        Schema::table('creances', function (Blueprint $table) {
            $table->unsignedBigInteger('vente_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creances', function (Blueprint $table) {
            $table->unsignedBigInteger('vente_id')->nullable(false)->change();
        });

        Schema::table('creances', function (Blueprint $table) {
            $table->dropUnique(['commande_id']);
            $table->dropForeign(['commande_id']);
            $table->dropColumn('commande_id');
        });
    }
};
