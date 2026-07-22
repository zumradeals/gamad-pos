<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same reasoning as the paiements migration: nullable, set only at
     * clôture validation time, existing rows correctly get NULL.
     */
    public function up(): void
    {
        Schema::table('versements', function (Blueprint $table) {
            $table->foreignId('cloture_id')->nullable()->after('creance_id')->constrained('clotures')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('versements', function (Blueprint $table) {
            $table->dropForeign(['cloture_id']);
            $table->dropColumn('cloture_id');
        });
    }
};
