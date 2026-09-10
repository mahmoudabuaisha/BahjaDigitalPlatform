<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * رسمة الفئة كانت تُختار حسب المعرّف اللاتيني، وبعد السماح بمعرّفات
 * عربية صار يلزم حقل مستقل يحفظ الرسمة كي لا تضيع عند تغيير المعرّف.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('scene', 30)->nullable()->after('icon');
        });

        // الفئات القائمة: رسمتها هي معرّفها الحالي قبل أي تغيير
        DB::table('categories')->update(['scene' => DB::raw('slug')]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('scene');
        });
    }
};
