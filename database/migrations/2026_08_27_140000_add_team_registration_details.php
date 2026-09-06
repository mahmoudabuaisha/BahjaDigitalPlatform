<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بيانات تسجيل الفرق الكاملة: نوع الجهة، المحافظة والمقر، هاتف الطوارئ،
 * مناطق التغطية، الأنشطة المتقنة، القدرات العددية، وختم تعهد سلامة الأطفال.
 * تُجمع في الطلب وتنتقل إلى ملف الفريق عند الاعتماد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_applications', function (Blueprint $table): void {
            $table->string('org_type', 20)->nullable()->after('team_name');
            $table->foreignId('area_id')->nullable()->after('org_type')->constrained('areas')->nullOnDelete();
            $table->string('base_location', 160)->nullable()->after('area_id');
            $table->string('emergency_phone', 30)->nullable()->after('contact_phone');
            $table->json('coverage_areas')->nullable()->after('geographic_scope');
            $table->json('activities')->nullable()->after('coverage_areas');
            $table->unsignedSmallInteger('volunteers_count')->nullable()->after('activities');
            $table->unsignedSmallInteger('capacity_per_event')->nullable()->after('volunteers_count');
            $table->timestamp('pledge_accepted_at')->nullable()->after('capacity_per_event');
        });

        Schema::table('teams', function (Blueprint $table): void {
            $table->string('org_type', 20)->nullable()->after('name');
            $table->foreignId('area_id')->nullable()->after('org_type')->constrained('areas')->nullOnDelete();
            $table->string('base_location', 160)->nullable()->after('area_id');
            $table->string('emergency_phone', 30)->nullable()->after('whatsapp_phone');
            $table->string('coverage_details', 160)->nullable()->after('emergency_phone');
            $table->json('coverage_areas')->nullable()->after('coverage_details');
            $table->json('activities')->nullable()->after('coverage_areas');
            $table->unsignedSmallInteger('volunteers_count')->nullable()->after('activities');
            $table->unsignedSmallInteger('capacity_per_event')->nullable()->after('volunteers_count');
        });
    }

    public function down(): void
    {
        Schema::table('team_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('area_id');
            $table->dropColumn(['org_type', 'base_location', 'emergency_phone', 'coverage_areas', 'activities', 'volunteers_count', 'capacity_per_event', 'pledge_accepted_at']);
        });

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('area_id');
            $table->dropColumn(['org_type', 'base_location', 'emergency_phone', 'coverage_details', 'coverage_areas', 'activities', 'volunteers_count', 'capacity_per_event']);
        });
    }
};
