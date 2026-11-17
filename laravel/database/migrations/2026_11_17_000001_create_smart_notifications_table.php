<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('smart_notifications')) {
            Schema::create('smart_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 50)->default('general');
                $table->string('title', 255);
                $table->text('message');
                $table->string('action_url', 500)->nullable();
                $table->string('action_label', 100)->nullable();
                $table->string('icon', 50)->default('bi-bell-fill');
                $table->string('badge_color', 30)->default('primary');
                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_read', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_notifications');
    }
};
