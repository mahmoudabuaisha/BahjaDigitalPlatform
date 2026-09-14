<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * رسائل «تواصلوا معنا» تنتقل من جدول التقييمات إلى جدول خاص بها:
 * لها موضوع وبريد وحالة معالجة، ولا علاقة لها بالنجوم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 120)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('subject', 120)->nullable();
            $table->text('message');

            // تُغلق حين تردّ الإدارة — والمفتوحة منها هي عدّاد القائمة الجانبية
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['handled_at', 'created_at']);
        });

        // الرسائل القديمة كانت تُحفظ في جدول التقييمات بلا تقييم ولا فعالية — ننقلها كما هي
        $legacy = DB::table('feedback')
            ->whereNull('rating')
            ->whereNull('event_id')
            ->whereNotNull('message')
            ->orderBy('id')
            ->get();

        foreach ($legacy as $row) {
            DB::table('contact_messages')->insert([
                'name' => $row->contact_name ?: 'غير مذكور',
                'email' => $row->contact_email,
                'phone' => $row->contact_phone,
                'subject' => $row->subject,
                'message' => $row->message,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        DB::table('feedback')->whereIn('id', $legacy->pluck('id'))->delete();
    }

    public function down(): void
    {
        foreach (DB::table('contact_messages')->orderBy('id')->get() as $row) {
            DB::table('feedback')->insert([
                'source' => 'family',
                'message' => $row->message,
                'subject' => $row->subject,
                'contact_name' => $row->name,
                'contact_email' => $row->email,
                'contact_phone' => $row->phone,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::dropIfExists('contact_messages');
    }
};
