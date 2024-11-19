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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['ADMIN', 'MEMBER','VOLUNTEER'])->default('MEMBER');
            $table->enum('level', ['1','2','3','4','5'])->nullable();
            $table->enum('level_name', ['Beginner','Lower-Intermediate','Upper-Intermediate','Advanced','Professional'])->nullable();
            $table->integer('points')->default(0);
            $table->enum('status', ['active', 'banned'])->nullable();
            $table->bigInteger('matches_played')->default(0);
            $table->string('address')->nullable();
            $table->string('image')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('language')->nullable();
            $table->string('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('side_of_the_court')->nullable();
            $table->boolean('verify_email')->default(false);
            $table->string('verification_token')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->boolean('otp_verified_at')->default(false);
            $table->boolean('mute_notifications')->default(false);
            $table->string('google_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('adjust_level')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
