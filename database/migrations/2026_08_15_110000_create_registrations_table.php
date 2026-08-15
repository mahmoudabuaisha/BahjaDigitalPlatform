<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** حجز مقاعد العائلات في الفعاليات */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('children_count')->default(1);
            $table->string('status', 20)->default('confirmed');
            $table->string('note', 300)->nullable();
            $table->timestamps();

            // حجز واحد لكل عائلة في كل فعالية — التعديل يكون على الحجز نفسه
            $table->unique(['event_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
