<?php

use Illuminate\Support\Facades\Schedule;

// كل منتصف ليلة (بتوقيت غزة): الفعاليات المعتمدة الماضية تصبح "منفَّذة"
Schedule::command('events:mark-completed')->dailyAt('00:15');
