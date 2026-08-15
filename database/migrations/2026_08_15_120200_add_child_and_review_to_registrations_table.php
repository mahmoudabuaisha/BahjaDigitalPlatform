<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الحجز صار لطفل بعينه، ويمرّ على موافقة الفريق:
     * قيد المراجعة ← مقبول / مرفوض (مع سبب يصل وليّ الأمر).
     *
     * الهجرة مكتوبة لتُعاد بأمان: أوامر DDL في MySQL لا تُلغى ضمن معاملة،
     * فإن فشلت خطوة بقيت سابقاتها منفَّذة — لذلك تُفحص كل خطوة قبل تنفيذها.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('registrations', 'child_id')) {
                $table->foreignId('child_id')->nullable()->after('user_id')
                    ->constrained('children')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('registrations', 'review_note')) {
                $table->string('review_note', 300)->nullable()->after('note');
            }

            if (! Schema::hasColumn('registrations', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('review_note');
            }
        });

        // MySQL يسند المفتاح الأجنبي event_id إلى الفهرس الفريد
        // (event_id, user_id)، ويرفض إسقاطه ما لم يوجد فهرس بديل — الخطأ 1553
        if (! $this->hasIndex('registrations_event_id_index')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->index('event_id');
            });
        }

        // حجز واحد لكل طفل في كل فعالية بدل حجز واحد لكل عائلة
        if ($this->hasIndex('registrations_event_id_user_id_unique')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->dropUnique(['event_id', 'user_id']);
            });
        }

        if (! $this->hasIndex('registrations_event_id_child_id_unique')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->unique(['event_id', 'child_id']);
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('registrations_event_id_child_id_unique')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->dropUnique(['event_id', 'child_id']);
            });
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->unique(['event_id', 'user_id']);
            $table->dropConstrainedForeignId('child_id');
            $table->dropColumn(['review_note', 'reviewed_at']);
        });

        if ($this->hasIndex('registrations_event_id_index')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->dropIndex('registrations_event_id_index');
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        foreach (Schema::getIndexes('registrations') as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }
};
