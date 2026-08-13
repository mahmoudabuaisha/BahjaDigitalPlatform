<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 60)->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('contact_name')->nullable();
            // صيغة دولية (9705xxxxxxxx) لبناء روابط wa.me مباشرة
            $table->string('whatsapp_phone', 20)->nullable();
            $table->json('social_links')->nullable();
            // false = طلب انضمام بانتظار موافقة الإدارة
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
