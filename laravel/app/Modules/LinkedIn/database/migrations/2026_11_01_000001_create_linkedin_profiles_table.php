<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linkedin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_profile_id')->nullable()->constrained('role_profiles')->nullOnDelete();
            $table->string('current_headline', 255)->nullable();
            $table->text('current_about')->nullable();
            $table->text('current_experience')->nullable();
            $table->string('optimized_headline', 255)->nullable();
            $table->text('optimized_about')->nullable();
            $table->text('optimized_experience')->nullable();
            $table->json('headline_variations')->nullable();
            $table->unsignedTinyInteger('recruiter_score')->default(65);
            $table->json('missing_keywords')->nullable();
            $table->text('optimization_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'role_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linkedin_profiles');
    }
};

