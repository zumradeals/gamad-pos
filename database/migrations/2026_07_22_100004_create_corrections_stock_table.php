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
        Schema::create('corrections_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->string('emplacement_type');
            $table->unsignedBigInteger('emplacement_id');
            $table->foreignId('ligne_inventaire_id')->nullable()->constrained('lignes_inventaire')->nullOnDelete();
            $table->foreignId('mouvement_stock_id')->nullable()->constrained('mouvements_stock')->nullOnDelete();
            $table->decimal('ecart', 12, 3);
            $table->string('motif')->nullable();
            $table->foreignId('autorise_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('statut');
            $table->timestamps();

            $table->index(['emplacement_type', 'emplacement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corrections_stock');
    }
};
