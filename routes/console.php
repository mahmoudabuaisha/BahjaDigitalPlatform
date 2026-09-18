<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
 * الاستضافة المشتركة تعطّل proc_open، فلا يستطيع المجدول تشغيل الأوامر كعمليات
 * منفصلة (Schedule::command تفشل بصمت كل دقيقة وتُسجَّل خطأً). لذلك تُشغَّل
 * الأوامر داخل عملية schedule:run نفسها عبر Artisan::call.
 */
$inProcess = fn (string $command, array $parameters = []) => Schedule::call(fn () => Artisan::call($command, $parameters))
    ->name($command);

// نبضة كل دقيقة تُظهر في «صحة النظام» أن الكرون حيّ وأن المهام تُنفَّذ داخله
Schedule::call(fn () => Cache::forever('schedule:heartbeat', now()->toDateTimeString()))
    ->name('heartbeat')
    ->everyMinute();

// كل منتصف ليلة: المنتهية تصبح "منفَّذة" ليفتح الحضور، والقديمة تؤرشَف (القسم 6.2)
$inProcess('events:archive')->dailyAt('00:15');

// كل دقيقة: فعالية معتمدة حان موعد نشرها المجدول تظهر ويُبثّ إعلانها (القسم 6.1)
$inProcess('events:release-scheduled')->everyMinute();

// كل صباح: تذكير من حجز مقعداً في فعالية الغد
$inProcess('registrations:remind')->dailyAt('09:00');

// كل مساء: «غداً في حيّكم» — الوعد الأساسي للمنصّة يصل الهاتف قبل النوم
$inProcess('push:tomorrow')->dailyAt('18:30');

// كل سبت صباحاً: نشرة الأسبوع البريدية بفعاليات الأيام السبعة القادمة
$inProcess('newsletter:send')->weeklyOn(6, '09:00');

// الاستضافة المشتركة بلا عامل طابور دائم: رسائل البريد المُنتظرة تُصرَّف كل
// دقيقة ضمن schedule:run نفسه ثم يتوقف العامل فوراً. القفل ينتهي بعد خمس
// دقائق كي لا يعطّل الطابور يوماً كاملاً إن قُتلت عملية في منتصفها.
$inProcess('queue:work', ['--stop-when-empty' => true, '--tries' => 3, '--max-time' => 45])
    ->everyMinute()
    ->withoutOverlapping(5);
