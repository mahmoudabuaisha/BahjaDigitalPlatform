<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مشتركو النشرة البريدية: بريد يؤكَّد برابط، ومحافظة اختيارية تحصر
 * النشرة في فعاليات المنطقة، ورابط إلغاء بضغطة واحدة في كل رسالة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 120)->unique();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            // رمز عشوائي في روابط التأكيد والإلغاء — لا يُخمَّن ولا ينتهي
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['confirmed_at', 'unsubscribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
