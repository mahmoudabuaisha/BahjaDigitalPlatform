<?php

use Illuminate\Support\Facades\Schedule;

// كل منتصف ليلة: المنتهية تصبح "منفَّذة" ليفتح الحضور، والقديمة تؤرشَف (القسم 6.2)
Schedule::command('events:archive')->dailyAt('00:15');

// كل صباح: تذكير من حجز مقعداً في فعالية الغد
Schedule::command('registrations:remind')->dailyAt('09:00');
