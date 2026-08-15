<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** الفئة العمرية: أول ما تسأل عنه العائلة قبل أن تُحضر طفلها */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('age_min')->nullable()->after('expected_children');
            $table->unsignedTinyInteger('age_max')->nullable()->after('age_min');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['age_min', 'age_max']);
        });
    }
};
