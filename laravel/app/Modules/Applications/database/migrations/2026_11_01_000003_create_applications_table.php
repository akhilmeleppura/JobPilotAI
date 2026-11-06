<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registry_id')->constrained('application_registry')->cascadeOnDelete();
            $table->foreignId('job_vacancy_id')->nullable()->constrained('job_vacancies')->nullOnDelete();
            $table->foreignId('role_profile_id')->nullable()->constrained('role_profiles')->nullOnDelete();
            $table->unsignedBigInteger('resume_id')->nullable();
            $table->unsignedBigInteger('resume_version_id')->nullable();
            $table->string('applied_via', 30)->default('Direct'); // Email, Portal, LinkedIn, Direct
            $table->string('discovered_from', 50)->default('Manual');
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->unsignedBigInteger('email_account_id')->nullable();
            $table->string('application_status', 30)->default('Draft'); // Draft, Staged, Sent, Replied, Interview, Offer, Rejected
            $table->string('email_delivery_status', 30)->default('NotApplicable'); // NotApplicable, Pending, Sent, Failed, Bounced
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'application_status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

