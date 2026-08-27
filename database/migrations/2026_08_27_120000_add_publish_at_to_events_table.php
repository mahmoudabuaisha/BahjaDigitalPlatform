<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نافذة النشر المؤجَّل (القسم 6.1): «معتمدة» تعني القبول،
 * والظهور للعائلات قد يُجدول لموعد لاحق تختاره الإدارة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->timestamp('publish_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('publish_at');
        });
    }
};
