<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. email_provider_accounts
        Schema::create('email_provider_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50); // gmail_api, google_apps_script, outlook_graph, zoho_mail, mailjet, smtp2go, etc.
            $table->string('account_email');
            $table->string('plan_name')->default('Free Tier');
            $table->string('connection_status', 30)->default('CONNECTED'); // CONNECTED, EXPIRED, ERROR, DISCONNECTED
            $table->string('policy_status', 30)->default('REQUIRES_REVIEW'); // VERIFIED_ALLOWED, REQUIRES_REVIEW, PROHIBITED, UNKNOWN
            $table->boolean('enabled')->default(true);
            $table->boolean('is_paused')->default(false);
            $table->string('pause_reason')->nullable();
            $table->unsignedInteger('custom_safety_cap')->nullable(); // Optional user safety cap, disabled by default
            $table->longText('encrypted_credentials')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'account_email'], 'user_prov_email_unique');
            $table->index(['user_id', 'enabled', 'connection_status']);
        });

        // 2. email_provider_allowances
        Schema::create('email_provider_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_account_id')->constrained('email_provider_accounts')->cascadeOnDelete();
            $table->string('allowance_type', 30)->default('daily'); // hourly, 5_hour_window, daily, rolling_24h, monthly, billing_period
            $table->unsignedInteger('allowance_limit')->nullable(); // Null if provider doesn't expose fixed limit
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('remaining_count')->nullable(); // Null if exact remaining is unavailable
            $table->string('quota_source', 30)->default('LOCALLY_MEASURED'); // PROVIDER_REPORTED, DOCUMENTED_PLAN, LOCALLY_MEASURED, ESTIMATED, UNKNOWN
            $table->timestamp('measured_at')->nullable();
            $table->timestamp('reset_at')->nullable();
            $table->string('status', 30)->default('ACTIVE'); // ACTIVE, WARNING, EXHAUSTED, UNKNOWN
            $table->timestamps();

            $table->index(['provider_account_id', 'allowance_type']);
        });

        // 3. email_provider_usage
        Schema::create('email_provider_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_account_id')->constrained('email_provider_accounts')->cascadeOnDelete();
            $table->string('message_id')->nullable();
            $table->unsignedInteger('recipient_count')->default(1);
            $table->timestamp('sent_at');
            $table->text('provider_response')->nullable();
            $table->string('delivery_status', 30)->default('SENT'); // SENT, QUEUED, FAILED, BOUNCED
            $table->timestamps();

            $table->index(['provider_account_id', 'sent_at']);
        });

        // 4. email_provider_errors
        Schema::create('email_provider_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_account_id')->constrained('email_provider_accounts')->cascadeOnDelete();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message');
            $table->timestamp('occurred_at');
            $table->string('retry_status', 30)->default('RESOLVED'); // FAILED, RETRYING, RESOLVED, IGNORED
            $table->timestamps();

            $table->index(['provider_account_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_provider_errors');
        Schema::dropIfExists('email_provider_usage');
        Schema::dropIfExists('email_provider_allowances');
        Schema::dropIfExists('email_provider_accounts');
    }
};

