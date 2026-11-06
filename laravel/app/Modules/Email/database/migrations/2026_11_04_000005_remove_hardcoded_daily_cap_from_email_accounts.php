<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_accounts')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->unsignedInteger('daily_cap')->nullable()->default(null)->change();
            });

            DB::table('email_accounts')->where('daily_cap', 200)->update(['daily_cap' => null]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};
