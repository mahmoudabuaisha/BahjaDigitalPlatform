<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * جداول خطة الإنتاج (القسم 9 من الوثيقة):
     * مراجعات الفعاليات، السلاسل الأسبوعية، طلبات انضمام الفرق،
     * تقارير الحضور، سجل التدقيق، أهداف الأثر — ومعرفات ULID عامة.
     */
    public function up(): void
    {
        // التكرار الأسبوعي: سلسلة تجمع مرات مستقلة (حضور وإلغاء لكل مرة)
        Schema::create('event_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('recurrence_rule', 20)->default('weekly');
            $table->date('starts_on');
            $table->unsignedTinyInteger('occurrence_count');
            $table->timestamps();
        });

        // معرّف عام غير قابل للتخمين للروابط وQR — الرابط يبقى ثابتاً
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'public_id')) {
                $table->ulid('public_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('events', 'series_id')) {
                $table->foreignId('series_id')->nullable()->after('team_id')
                    ->constrained('event_series')->nullOnDelete();
            }

            if (! Schema::hasColumn('events', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('approved_at');
            }
        });

        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'public_id')) {
                $table->ulid('public_id')->nullable()->after('id');
            }
        });

        foreach (['events', 'teams'] as $tableName) {
            foreach (DB::table($tableName)->whereNull('public_id')->pluck('id') as $id) {
                DB::table($tableName)->where('id', $id)->update(['public_id' => (string) Str::ulid()]);
            }
        }

        Schema::table('events', fn (Blueprint $table) => $table->unique('public_id'));
        Schema::table('teams', fn (Blueprint $table) => $table->unique('public_id'));

        // تعديل فعالية منشورة لا يمسّها: يُحفظ نسخةً تنتظر الاعتماد
        Schema::create('event_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_reason', 500)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->unique(['event_id', 'version']);
        });

        // طلبات انضمام الفرق التطوعية — لا تشغيل قبل الاعتماد
        Schema::create('team_applications', function (Blueprint $table) {
            $table->id();
            $table->string('team_name', 120);
            $table->string('contact_name', 120);
            $table->string('contact_email');
            $table->string('contact_phone', 30);
            $table->text('description');
            $table->string('geographic_scope', 160)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('decision_reason', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // الحضور الفعلي: أرقام إجمالية فقط، سجل واحد لكل فعالية، والأثر يعتمد عليه
        Schema::create('attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('children_actual');
            $table->unsignedSmallInteger('guardians_actual')->default(0);
            $table->string('notes_private', 500)->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // سجل رقابي للقرارات الحساسة — قراءة فقط من اللوحة
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at');

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });

        // أهداف الأثر بفترات بدل رقم ثابت في الإعدادات
        Schema::create('impact_targets', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('children_target');
            $table->unsignedInteger('indirect_target')->default(0);
            $table->timestamps();
        });

        // تقييم مجهول: بصمة جهاز مموّهة بدل أي بيانات شخصية
        Schema::table('feedback', function (Blueprint $table) {
            if (! Schema::hasColumn('feedback', 'device_hash')) {
                $table->string('device_hash', 64)->nullable()->after('rating')->index();
            }
        });

        // مستوى ظهور المكان — حماية ميدانية (القسم 15.6)
        Schema::table('shelter_centers', function (Blueprint $table) {
            if (! Schema::hasColumn('shelter_centers', 'visibility')) {
                $table->string('visibility', 30)->default('public_exact')->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shelter_centers', fn (Blueprint $table) => $table->dropColumn('visibility'));
        Schema::table('feedback', fn (Blueprint $table) => $table->dropColumn('device_hash'));
        Schema::dropIfExists('impact_targets');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attendance_reports');
        Schema::dropIfExists('team_applications');
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('series_id');
            $table->dropColumn(['public_id', 'archived_at']);
        });
        Schema::dropIfExists('event_series');
        Schema::dropIfExists('event_revisions');
        Schema::table('teams', fn (Blueprint $table) => $table->dropColumn('public_id'));
    }
};
