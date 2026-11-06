<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('file_type', 30)->default('CSV'); // CSV, XLSX, GoogleSheets, Text
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('duplicates_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('column_mapping')->nullable();
            $table->string('status', 30)->default('Completed'); // Processing, Completed, Failed
            $table->text('error_summary')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};

