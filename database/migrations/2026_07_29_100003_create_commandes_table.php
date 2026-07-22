<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * devis_id est unique : un devis accepté ne se transforme en commande
     * qu'une seule fois (CommandeService le vérifie aussi explicitement,
     * pour lever une erreur applicative propre plutôt qu'une violation de
     * contrainte brute).
     */
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('point_de_vente_id')->constrained('points_de_vente')->cascadeOnDelete();
            $table->foreignId('devis_id')->nullable()->unique()->constrained('devis')->nullOnDelete();
            $table->string('statut');
            $table->decimal('montant_total', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
