<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clotures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_de_vente_id')->constrained('points_de_vente')->cascadeOnDelete();
            $table->dateTime('ouverte_a');
            $table->string('statut');
            $table->decimal('especes_attendues', 12, 2)->nullable();
            $table->decimal('especes_comptees', 12, 2)->nullable();
            $table->decimal('ecart', 12, 2)->nullable();
            // Graine pour le Cycle 3 (Dépenses) : pas de table ni de saisie
            // dans ce chantier, seulement le champ pour éviter une migration
            // supplémentaire quand ce module arrivera.
            $table->decimal('depenses_total', 12, 2)->nullable();
            $table->foreignId('validee_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validee_a')->nullable();
            $table->string('motif_reouverture')->nullable();
            $table->foreignId('reouverte_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reouverte_a')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clotures');
    }
};
