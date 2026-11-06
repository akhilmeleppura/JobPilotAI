<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 40); // gemini, groq, openrouter, ollama
            $table->string('model', 100);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost_estimate_usd', 8, 6)->default(0.000000);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 30)->default('success'); // success, blocked_by_policy, failed, fallback_used
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
    }
};

