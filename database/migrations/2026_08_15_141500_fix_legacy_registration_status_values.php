<?php

use App\Enums\RegistrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * قبل مسار الموافقة كانت الحجوزات تُنشأ بحالة «confirmed»،
     * وهي قيمة لم تعد ضمن RegistrationStatus — فتُسقط كلَّ شاشة تعرض الحالة.
     * تُحوَّل الصفوف القديمة إلى «مقبول»، ويصير الأصل «قيد المراجعة».
     */
    public function up(): void
    {
        DB::table('registrations')
            ->where('status', 'confirmed')
            ->update(['status' => RegistrationStatus::Accepted->value]);

        DB::table('registrations')
            ->whereNotIn('status', array_column(RegistrationStatus::cases(), 'value'))
            ->update(['status' => RegistrationStatus::Pending->value]);

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('status', 20)->default(RegistrationStatus::Pending->value)->change();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('status', 20)->default('confirmed')->change();
        });
    }
};
