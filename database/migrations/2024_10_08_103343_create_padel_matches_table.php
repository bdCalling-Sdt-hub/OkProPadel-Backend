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
        Schema::create('padel_matches', function (Blueprint $table) {
            $table->id();
            $table->string('latitude');
            $table->string('longitude');
            $table->string('mind_text',120);
            $table->string('selected_level');
            $table->enum('level', ['1','2','3','4','5']);
            $table->enum('level_name', ['Beginner','Lower-Intermediate','Upper-Intermediate','Advanced','Professonal']);
            $table->foreignId('creator_id')->constrained('users');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('padel_matches');
    }
};
