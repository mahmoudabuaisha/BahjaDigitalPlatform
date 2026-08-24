<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** MFA للمشرفين (القسم 15.2) — سرّ تطبيق المصادقة ورموز الاسترداد، مشفَّرة */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'app_authentication_secret')) {
                $table->text('app_authentication_secret')->nullable();
            }

            if (! Schema::hasColumn('users', 'app_authentication_recovery_codes')) {
                $table->text('app_authentication_recovery_codes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};
