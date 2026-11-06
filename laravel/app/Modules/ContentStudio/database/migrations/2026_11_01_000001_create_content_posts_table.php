<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_profile_id')->nullable()->constrained('role_profiles')->nullOnDelete();
            $table->string('title', 255);
            $table->string('topic', 255);
            $table->string('category', 100)->default('architecture');
            $table->text('content');
            $table->text('image_prompt')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('image_provider', 100)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('status', 50)->default('draft');
            $table->json('metrics')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_posts');
    }
};

