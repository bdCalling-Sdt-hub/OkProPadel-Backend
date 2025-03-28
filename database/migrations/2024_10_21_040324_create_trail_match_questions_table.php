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
        Schema::create('trail_match_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question',1000);
            $table->string('options',1000);
            $table->string('question_2',1000);
            $table->string('options_2',1000);
            $table->enum('question_es', ['english', 'spanish'])->default('english');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_match_questions');
    }
};
