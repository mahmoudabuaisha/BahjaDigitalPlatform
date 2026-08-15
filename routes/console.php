<?php

use Illuminate\Support\Facades\Schedule;

// كل منتصف ليلة (بتوقيت غزة): الفعاليات المعتمدة الماضية تصبح "منفَّذة"
Schedule::command('events:mark-completed')->dailyAt('00:15');

// كل صباح: تذكير من حجز مقعداً في فعالية الغد
Schedule::command('registrations:remind')->dailyAt('09:00');
