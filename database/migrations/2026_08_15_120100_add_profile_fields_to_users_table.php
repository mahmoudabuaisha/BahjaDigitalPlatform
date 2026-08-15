<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** بيانات الملف الشخصي لوليّ الأمر — كلها اختيارية */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('gender', 10)->nullable()->after('birth_date');
            $table->foreignId('area_id')->nullable()->after('gender')->constrained()->nullOnDelete();
            $table->string('address', 200)->nullable()->after('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
            $table->dropColumn(['birth_date', 'gender', 'address']);
        });
    }
};
