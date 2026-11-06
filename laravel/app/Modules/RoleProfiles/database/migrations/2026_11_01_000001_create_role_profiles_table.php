<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->json('target_titles')->nullable();
            $table->json('technical_skills')->nullable();
            $table->json('secondary_skills')->nullable();
            $table->decimal('experience_years', 4, 1)->default(0);
            $table->text('summary')->nullable();
            $table->json('location_preferences')->nullable();
            $table->json('work_modes')->nullable(); // Remote, Hybrid, Onsite
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->json('search_keywords')->nullable();
            $table->unsignedBigInteger('default_resume_id')->nullable();
            $table->text('email_signature')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_profiles');
    }
};
