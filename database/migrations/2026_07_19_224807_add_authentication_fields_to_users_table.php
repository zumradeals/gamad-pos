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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('telephone')->unique()->after('name');
            $table->string('pin')->nullable()->after('telephone');
            $table->foreignId('entreprise_id')->nullable()->after('pin')->constrained('entreprises')->nullOnDelete();
            $table->string('role')->nullable()->after('entreprise_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entreprise_id');
            $table->dropColumn(['telephone', 'pin', 'role']);
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
