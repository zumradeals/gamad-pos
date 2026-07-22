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
        Schema::create('inventaires', function (Blueprint $table) {
            $table->id();
            $table->string('emplacement_type');
            $table->unsignedBigInteger('emplacement_id');
            $table->date('date');
            $table->foreignId('responsable_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['emplacement_type', 'emplacement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaires');
    }
};
