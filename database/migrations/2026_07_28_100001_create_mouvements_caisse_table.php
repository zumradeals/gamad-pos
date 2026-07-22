<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * cloture_id est nullable dans le schéma (même forme de colonne que
     * paiements/versements/dépenses) mais, à la différence de ces
     * dernières, n'est jamais laissé null en pratique après création : un
     * mouvement de caisse naît toujours déjà rattaché à la clôture ouverte
     * (fonds initial à l'ouverture, entrée/sortie refusée sans clôture
     * ouverte) — voir ClotureService.
     */
    public function up(): void
    {
        Schema::create('mouvements_caisse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_de_vente_id')->constrained('points_de_vente')->cascadeOnDelete();
            $table->foreignId('cloture_id')->nullable()->constrained('clotures')->nullOnDelete();
            $table->string('type');
            $table->decimal('montant', 12, 2);
            $table->string('motif')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvements_caisse');
    }
};
