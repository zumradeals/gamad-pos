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
        Schema::create('versements_fournisseur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dette_fournisseur_id')->constrained('dettes_fournisseur')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versements_fournisseur');
    }
};
