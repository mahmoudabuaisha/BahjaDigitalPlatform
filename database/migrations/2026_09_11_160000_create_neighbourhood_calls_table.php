<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نداء الحيّ: عائلة لا تجد فعالية قريبة ترفع يدها. النداءات تُجمَّع
 * فتعرف الفرق أين ينتظر الأطفال — ولا يُعرض نداء فرد أبداً للفرق،
 * بل العدد المجمَّع فقط، كي لا يُستدلّ على عائلة بعينها.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neighbourhood_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained();
            $table->foreignId('shelter_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('age_band', 20)->nullable();
            $table->string('note', 200)->nullable();
            $table->unsignedSmallInteger('children_count')->default(1);

            // تُغلق حين تصل فعالية إلى المكان، فنعرف أن النداء لُبّي
            $table->foreignId('answered_event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['area_id', 'answered_at']);
            $table->index(['shelter_center_id', 'answered_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighbourhood_calls');
    }
};
