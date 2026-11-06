<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_group', 40); // ai, email, job_sources, cloud_storage, system
            $table->string('provider_key', 50); // google_ai_studio, groq, openrouter, ollama, gmail, etc.
            $table->string('provider_name', 100);
            $table->longText('encrypted_secrets')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('health_status', 30)->default('Untested'); // Healthy, Degraded, Failed, Untested
            $table->string('health_message', 500)->nullable();
            $table->unsignedInteger('requests_today')->default(0);
            $table->unsignedInteger('quota_limit_daily')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider_key'], 'user_provider_unique');
            $table->index(['user_id', 'provider_group', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_credentials');
    }
};

