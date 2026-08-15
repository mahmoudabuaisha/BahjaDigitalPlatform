<?php

use App\Http\Controllers\Auth\FamilyAuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventIndexController;
use App\Http\Controllers\EventQrController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamPublicController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/events', EventIndexController::class)->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/e/{event}', [EventController::class, 'short'])->name('events.short');
Route::get('/events/{event}/qr.svg', EventQrController::class)->name('events.qr');
Route::post('/events/{event}/feedback', [FeedbackController::class, 'storeForEvent'])
    ->middleware('throttle:feedback')
    ->name('events.feedback');

Route::get('/organizers', OrganizerController::class)->name('organizers');
Route::get('/teams/{team:slug}', [TeamPublicController::class, 'show'])->name('teams.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:feedback')
    ->name('contact.store');

Route::view('/about', 'pages.about')->name('about');
Route::view('/guide', 'pages.guide')->name('guide');

// ── حسابات العائلات ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [FamilyAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [FamilyAuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [FamilyAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [FamilyAuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::post('/logout', [FamilyAuthController::class, 'logout'])->middleware('auth')->name('logout');

// ── حجز المقاعد ──
Route::middleware('auth')->group(function () {
    Route::get('/my-events', [RegistrationController::class, 'index'])->name('my-events');
    Route::post('/events/{event}/register', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::delete('/events/{event}/register', [RegistrationController::class, 'destroy'])->name('registrations.destroy');
});

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
