<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_identity_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registry_id')->constrained('application_registry')->cascadeOnDelete();
            $table->string('key_type', 40); // SHA256_FINGERPRINT, REQ_ID, APPLY_URL, CANONICAL_URL
            $table->string('key_value', 255)->index();
            $table->timestamps();

            $table->index(['key_type', 'key_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_identity_keys');
    }
};

