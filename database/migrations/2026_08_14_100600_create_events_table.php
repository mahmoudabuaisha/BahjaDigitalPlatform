<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            // نسخة منزوعة التطبيع من منطقة المركز — تغطي أيضاً الفعاليات في مواقع حرة بلا مركز
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->foreignId('shelter_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('location_details')->nullable();
            $table->date('start_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('rejection_reason', 500)->nullable();
            $table->unsignedSmallInteger('expected_children')->nullable();
            // تُسجَّل بعد التنفيذ — المصدر الوحيد المعتمد لتقارير الأثر
            $table->unsignedSmallInteger('actual_children')->nullable();
            $table->unsignedSmallInteger('actual_caregivers')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
            $table->index(['area_id', 'status', 'start_date']);
            $table->index(['team_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
