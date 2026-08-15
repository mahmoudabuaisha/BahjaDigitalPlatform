<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الحجز صار لطفل بعينه، ويمرّ على موافقة الفريق:
     * قيد المراجعة ← مقبول / مرفوض (مع سبب يصل وليّ الأمر).
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('child_id')->nullable()->after('user_id')->constrained('children')->cascadeOnDelete();
            $table->string('review_note', 300)->nullable()->after('note');
            $table->timestamp('reviewed_at')->nullable()->after('review_note');
        });

        // حجز واحد لكل طفل في كل فعالية بدل حجز واحد لكل عائلة
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'user_id']);
            $table->unique(['event_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'child_id']);
            $table->unique(['event_id', 'user_id']);
            $table->dropConstrainedForeignId('child_id');
            $table->dropColumn(['review_note', 'reviewed_at']);
        });
    }
};
