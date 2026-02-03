<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('login_attempts', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('login_attempts', 'failure_reason')) {
                $table->string('failure_reason')->nullable()->after('success');
            }
            if (!Schema::hasColumn('login_attempts', 'updated_at')) {
                $table->timestamp('updated_at')->useCurrent()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('login_attempts', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('login_attempts', 'failure_reason')) {
                $table->dropColumn('failure_reason');
            }
            if (Schema::hasColumn('login_attempts', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
