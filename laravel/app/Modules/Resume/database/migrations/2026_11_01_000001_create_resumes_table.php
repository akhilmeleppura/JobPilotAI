<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_profile_id')->nullable()->constrained('role_profiles')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->boolean('is_global_default')->default(false);
            $table->string('target_role_name')->nullable();
            $table->json('primary_skills')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug'], 'user_resume_slug_unique');
            $table->index(['user_id', 'is_global_default']);
            $table->index(['user_id', 'role_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};

