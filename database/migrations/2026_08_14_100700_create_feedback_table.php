<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            // null = تقييم عام للمنصة، وإلا فتقييم لفعالية بعينها
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 10)->default('family');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('message')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->timestamps();

            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
