<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('archive_year');
            $table->unsignedTinyInteger('archive_month');
            $table->unsignedInteger('records_count')->default(0);
            $table->string('jsonl_filename');
            $table->char('jsonl_checksum_sha256', 64);
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('csv_filename')->nullable();
            $table->char('csv_checksum_sha256', 64)->nullable();
            $table->string('gdrive_file_id')->nullable();
            $table->string('gdrive_sheet_id')->nullable();
            $table->string('status', 30)->default('Generated'); // Generated, Uploaded, Synced, Verified
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'archive_year', 'archive_month'], 'user_monthly_archive_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_archives');
    }
};

