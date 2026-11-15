<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. outlook_messages (Mail)
        if (!Schema::hasTable('outlook_messages')) {
            Schema::create('outlook_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('folder', 30)->default('inbox'); // inbox, sent, drafts, archive, deleted, junk
                $table->boolean('is_focused')->default(true); // Focused vs Other
                $table->boolean('is_read')->default(false);
                $table->boolean('is_flagged')->default(false);
                $table->boolean('is_pinned')->default(false);
                $table->string('sender_name')->nullable();
                $table->string('sender_email');
                $table->string('recipient_name')->nullable();
                $table->string('recipient_email');
                $table->string('subject', 255);
                $table->text('snippet')->nullable();
                $table->longText('body_html')->nullable();
                $table->string('category', 50)->default('Recruiter'); // Recruiter, Interview, Offer, General
                $table->boolean('has_attachments')->default(false);
                $table->timestamp('received_at')->useCurrent();
                $table->timestamps();

                $table->index(['user_id', 'folder', 'is_read'], 'out_msg_user_fld_read_idx');
                $table->index(['user_id', 'is_flagged'], 'out_msg_user_flag_idx');
            });
        }

        // 2. outlook_events (Calendar)
        if (!Schema::hasTable('outlook_events')) {
            Schema::create('outlook_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('location', 255)->nullable();
                $table->string('meeting_link', 500)->nullable();
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->boolean('is_all_day')->default(false);
                $table->string('category', 50)->default('Interview'); // Interview, Call, Deadline, FollowUp, Personal
                $table->string('color', 20)->default('#0078d4'); // Outlook blue, green, amber, red
                $table->string('company_name')->nullable();
                $table->string('interviewer_name')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'start_time'], 'out_evt_user_start_idx');
            });
        }

        // 3. outlook_contacts (People)
        if (!Schema::hasTable('outlook_contacts')) {
            Schema::create('outlook_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 255);
                $table->string('email', 255);
                $table->string('company', 255)->nullable();
                $table->string('job_title', 255)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('linkedin_url', 500)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'name'], 'out_cnt_user_name_idx');
            });
        }

        // 4. outlook_tasks (To-Do)
        if (!Schema::hasTable('outlook_tasks')) {
            Schema::create('outlook_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->boolean('is_completed')->default(false);
                $table->boolean('is_important')->default(false);
                $table->boolean('is_my_day')->default(false);
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_completed'], 'out_tsk_user_comp_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outlook_tasks');
        Schema::dropIfExists('outlook_contacts');
        Schema::dropIfExists('outlook_events');
        Schema::dropIfExists('outlook_messages');
    }
};
