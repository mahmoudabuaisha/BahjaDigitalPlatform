<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamPublicController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/e/{event}', [EventController::class, 'short'])->name('events.short');
Route::post('/events/{event}/feedback', [FeedbackController::class, 'storeForEvent'])
    ->middleware('throttle:feedback')
    ->name('events.feedback');

Route::get('/teams/{team:slug}', [TeamPublicController::class, 'show'])->name('teams.show');

Route::view('/guide', 'pages.guide')->name('guide');

Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:feedback')
    ->name('feedback.store');

Route::get('/api/v1/events', FeedController::class)->name('api.events');

Route::post('/t/e/{event}', [TrackController::class, 'event'])->name('track.event');

Route::view('/offline', 'pages.offline')->name('offline');
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
