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
        Schema::create('after_match_question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id');
            $table->foreignId('user_id');
            $table->json('questionnaire_id')->nullable();
            $table->json('answer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('after_match_question_answers');
    }
};
