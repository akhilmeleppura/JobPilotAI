<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Career Copilot Session');
            $table->foreignId('active_role_profile_id')->nullable()->constrained('role_profiles')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->string('sender', 30)->default('user'); // user, assistant, system
            $table->longText('message');
            $table->json('attachments')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};

