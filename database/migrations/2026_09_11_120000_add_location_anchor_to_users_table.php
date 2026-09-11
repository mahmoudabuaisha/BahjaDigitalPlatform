<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مرساة مكان العائلة: أقرب معلم يعرفه أهلها (مركز إيواء/مدرسة/مخيم).
 * لا إحداثيات ولا GPS — اختيار من قائمة، فالقرب يُحسب بالجيرة لا بالمسافة،
 * ولا يُنشر عن العائلة موقع دقيق أبداً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('shelter_center_id')->nullable()->after('area_id')
                ->constrained()->nullOnDelete();

            // النزوح متكرر: نعرف متى ضُبطت المرساة كي نسأل عن تحديثها بلطف
            $table->timestamp('location_set_at')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shelter_center_id');
            $table->dropColumn('location_set_at');
        });
    }
};
