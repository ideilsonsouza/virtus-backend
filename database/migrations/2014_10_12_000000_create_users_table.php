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
            $table->bigInteger('code')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password_reset_code')->nullable()->unique();
            $table->dateTime('password_reset_expiration')->nullable();
            $table->date('date_birth')->nullable();
            $table->bigInteger('doc_number')->nullable();
            $table->enum('gender', ['f', 'm', 'o'])->nullable();
            $table->boolean('receive_email_notifications')->default(true);
            $table->dateTime('date_agree_terms')->nullable();
            $table->text('terms')->nullable();
            $table->boolean('enabled')->default(true);
            $table->enum('role', ['user', 'super', 'team', 'invite'])->default('user');
            $table->json('settings')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
