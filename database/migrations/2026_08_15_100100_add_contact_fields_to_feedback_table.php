<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** رسائل صفحة "تواصلوا معنا" تصل عبر جدول التقييمات نفسه: موضوع وبريد */
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('subject', 120)->nullable()->after('message');
            $table->string('contact_email', 120)->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['subject', 'contact_email']);
        });
    }
};
