<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_type', 30)->default('Gmail'); // Gmail, MicrosoftGraph, Zoho, SMTP, LogRelay
            $table->string('email_address');
            $table->string('sender_name')->nullable();
            $table->longText('encrypted_credentials')->nullable();
            $table->unsignedInteger('daily_cap')->default(200);
            $table->unsignedInteger('sent_today')->default(0);
            $table->date('last_reset_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('pause_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'email_address'], 'user_email_unique');
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};

