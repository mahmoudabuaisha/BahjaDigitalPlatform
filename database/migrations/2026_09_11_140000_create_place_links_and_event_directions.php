<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * شبكة القرب: كم دقيقة مشياً بين مكان وآخر. معرفة يملكها أهل المكان
 * والفرق أدقّ من أي خدمة خرائط — فهم يعرفون الطريق المفتوح من المغلق.
 * الصلة متماثلة: صفّ واحد لكل زوج، والاستعلام يقرؤه في الاتجاهين.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_center_id')->constrained('shelter_centers')->cascadeOnDelete();
            $table->foreignId('to_center_id')->constrained('shelter_centers')->cascadeOnDelete();
            $table->unsignedSmallInteger('walk_minutes');
            $table->string('note', 160)->nullable();
            $table->timestamps();

            $table->unique(['from_center_id', 'to_center_id']);
            $table->index('to_center_id');
        });

        Schema::table('events', function (Blueprint $table): void {
            // «كيف تصلون؟» بالمعالم لا بالإحداثيات — يكتبها الفريق بلغة الناس
            $table->string('directions', 300)->nullable()->after('location_details');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_links');

        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('directions');
        });
    }
};
