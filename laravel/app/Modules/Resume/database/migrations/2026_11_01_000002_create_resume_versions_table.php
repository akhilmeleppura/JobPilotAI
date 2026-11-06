<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('template_name', 50)->default('backend_developer');
            $table->string('source_type', 30)->default('LaTeX'); // LaTeX, JSON, Markdown
            $table->longText('latex_source')->nullable();
            $table->json('parsed_content')->nullable();
            $table->string('pdf_path')->nullable();
            $table->unsignedTinyInteger('ats_score')->default(85);
            $table->text('optimization_notes')->nullable();
            $table->timestamps();

            $table->unique(['resume_id', 'version_number'], 'resume_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_versions');
    }
};

