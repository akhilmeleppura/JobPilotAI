<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('company_name');
            $table->string('job_title');
            $table->string('location')->nullable();
            $table->string('work_mode', 30)->default('Remote'); // Remote, Hybrid, Onsite
            $table->string('employment_type', 30)->default('Full-time'); // Full-time, Contract, Part-time
            $table->decimal('experience_min', 4, 1)->nullable();
            $table->decimal('experience_max', 4, 1)->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->json('skills')->nullable();
            $table->longText('description')->nullable();
            $table->date('posted_date')->nullable();
            $table->date('application_deadline')->nullable();
            $table->string('apply_method', 30)->default('Portal'); // Email, Portal, LinkedIn, Direct
            $table->string('apply_url', 1000)->nullable();
            $table->string('source_url', 1000)->nullable();
            $table->string('source_provider', 50)->default('Manual'); // Adzuna, Jooble, RemoteOK, Himalayas, Manual
            $table->string('external_job_id', 150)->nullable()->index();
            $table->string('hr_name')->nullable();
            $table->string('hr_email')->nullable()->index();
            $table->string('hr_email_source_url')->nullable();
            $table->char('vacancy_fingerprint', 64)->index();
            $table->string('status', 30)->default('Discovered'); // Discovered, Matched, Staged, Applied, Ignored
            $table->timestamps();

            $table->index(['company_name', 'job_title']);
            $table->index(['source_provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_vacancies');
    }
};

