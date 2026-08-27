<?php

use Illuminate\Support\Facades\Schedule;

// كل منتصف ليلة: المنتهية تصبح "منفَّذة" ليفتح الحضور، والقديمة تؤرشَف (القسم 6.2)
Schedule::command('events:archive')->dailyAt('00:15');

// كل دقيقة: فعالية معتمدة حان موعد نشرها المجدول تظهر ويُبثّ إعلانها (القسم 6.1)
Schedule::command('events:release-scheduled')->everyMinute();

// كل صباح: تذكير من حجز مقعداً في فعالية الغد
Schedule::command('registrations:remind')->dailyAt('09:00');

// الاستضافة المشتركة بلا عامل طابور دائم: رسائل البريد المُنتظرة تُصرَّف
// كل دقيقة ضمن نفس كرون schedule:run ثم يتوقف العامل فوراً
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=45')
    ->everyMinute()
    ->withoutOverlapping();
