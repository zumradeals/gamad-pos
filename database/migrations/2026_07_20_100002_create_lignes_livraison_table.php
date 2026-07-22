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
        Schema::create('lignes_livraison', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livraison_id')->constrained('livraisons')->cascadeOnDelete();
            $table->decimal('quantite', 12, 3);
            $table->dateTime('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lignes_livraison');
    }
};
