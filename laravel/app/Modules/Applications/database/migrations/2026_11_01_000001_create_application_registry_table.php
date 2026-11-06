<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_registry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('canonical_fingerprint', 64);
            $table->string('company_name_normalized')->index();
            $table->string('job_title_normalized')->index();
            $table->string('location_normalized')->nullable();
            $table->string('external_requisition_id')->nullable()->index();
            $table->char('source_url_hash', 64)->nullable()->index();
            $table->timestamp('first_discovered_at')->useCurrent();
            $table->timestamp('last_applied_at')->nullable();
            $table->string('status', 30)->default('Discovered'); // Discovered, Matched, Staged, Applied, Archived
            $table->timestamps();

            $table->unique(['user_id', 'canonical_fingerprint'], 'user_fingerprint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_registry');
    }
};

