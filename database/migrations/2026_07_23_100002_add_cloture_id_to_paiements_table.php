<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable and untouched at payment creation time — it is only set once,
     * at the moment a clôture validation rattaches this paiement en espèces.
     * Existing rows simply get NULL, which is exactly correct: nothing
     * recorded before this chantier was ever covered by a clôture.
     */
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->foreignId('cloture_id')->nullable()->after('vente_id')->constrained('clotures')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropForeign(['cloture_id']);
            $table->dropColumn('cloture_id');
        });
    }
};
