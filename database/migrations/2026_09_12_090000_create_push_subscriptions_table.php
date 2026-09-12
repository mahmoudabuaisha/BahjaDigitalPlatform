<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اشتراك جهاز في الإشعارات. العائلة الواحدة قد تملك أكثر من جهاز،
 * والجهاز الواحد يُلغي اشتراكه القديم من تلقائه فيبقى العنوان فريداً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();
            $table->string('p256dh', 120);
            $table->string('auth', 60);
            $table->string('device', 160)->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedTinyInteger('failures')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
