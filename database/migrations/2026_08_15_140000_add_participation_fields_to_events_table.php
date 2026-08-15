<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * حقول نموذج «إضافة فعالية» الذي يملؤه الفريق المنظِّم:
     * رسوم اختيارية، شروط وملاحظات، الفئة المستهدفة، وطريقة التسجيل
     * (مباشر يُقبل فوراً، أو بموافقة الفريق على كل حجز).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'fee')) {
                $table->decimal('fee', 8, 2)->nullable()->after('expected_children');
            }

            if (! Schema::hasColumn('events', 'terms')) {
                $table->text('terms')->nullable()->after('description');
            }

            if (! Schema::hasColumn('events', 'registration_mode')) {
                $table->string('registration_mode', 20)->default('approval')->after('fee');
            }

            if (! Schema::hasColumn('events', 'audience')) {
                $table->string('audience', 20)->default('all')->after('category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['fee', 'terms', 'registration_mode', 'audience']);
        });
    }
};
