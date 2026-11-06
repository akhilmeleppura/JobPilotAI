<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add priority and is_primary to email_provider_accounts
        Schema::table('email_provider_accounts', function (Blueprint $table) {
            $table->unsignedInteger('priority')->default(1)->after('enabled');
            $table->boolean('is_primary')->default(false)->after('priority');
        });

        // 2. Add attempt_uuid, idempotency_key, previous_provider, switch_count to email_logs
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('attempt_uuid', 64)->nullable()->after('application_id');
            $table->string('idempotency_key', 128)->nullable()->unique()->after('attempt_uuid');
            $table->string('previous_provider', 50)->nullable()->after('error_message');
            $table->unsignedInteger('switch_count')->default(0)->after('previous_provider');
        });

        // 3. Create email_provider_switches table
        Schema::create('email_provider_switches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->foreignId('email_log_id')->nullable()->constrained('email_logs')->nullOnDelete();
            $table->string('email_attempt_id', 64);
            $table->string('previous_provider', 50);
            $table->string('new_provider', 50);
            $table->text('reason');
            $table->string('quota_source', 50);
            $table->text('provider_response')->nullable();
            $table->string('policy_status', 50);
            $table->string('result', 50)->default('SWITCHED'); // SWITCHED, RECONCILED, BLOCKED, PRESERVED
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['email_attempt_id']);
        });

        // 4. Create email_switching_settings table
        Schema::create('email_switching_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('automatic_switching')->default(true);
            $table->string('provider_mode', 20)->default('AUTOMATIC'); // MANUAL, AUTOMATIC
            $table->boolean('allow_technical_failover')->default(true);
            $table->boolean('allow_quota_switch')->default(false); // Disabled by default per requirements
            $table->boolean('free_only_mode')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_switching_settings');
        Schema::dropIfExists('email_provider_switches');

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['attempt_uuid', 'idempotency_key', 'previous_provider', 'switch_count']);
        });

        Schema::table('email_provider_accounts', function (Blueprint $table) {
            $table->dropColumn(['priority', 'is_primary']);
        });
    }
};

