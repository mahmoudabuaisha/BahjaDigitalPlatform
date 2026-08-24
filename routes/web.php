<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\FamilyAuthController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventIndexController;
use App\Http\Controllers\EventQrController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizerAttendanceController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\OrganizerDashboardController;
use App\Http\Controllers\OrganizerEventController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamApplicationController;
use App\Http\Controllers\TeamPublicController;
use App\Http\Controllers\TrackController;
use App\Http\Middleware\EnsureTeamManager;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/events', EventIndexController::class)->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/e/{event}', [EventController::class, 'short'])->name('events.short');
Route::get('/r/{publicId}', [EventController::class, 'stable'])->name('events.stable');
Route::get('/events/{event}/qr.svg', EventQrController::class)->name('events.qr');
Route::post('/events/{event}/feedback', [FeedbackController::class, 'storeForEvent'])
    ->middleware('throttle:feedback')
    ->name('events.feedback');

Route::get('/organizers', OrganizerController::class)->name('organizers');
Route::get('/join-team', [TeamApplicationController::class, 'create'])->name('teams.join');
Route::post('/join-team', [TeamApplicationController::class, 'store'])
    ->middleware('throttle:feedback')
    ->name('teams.join.store');
Route::get('/teams/{team:slug}', [TeamPublicController::class, 'show'])->name('teams.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:feedback')
    ->name('contact.store');

Route::view('/about', 'pages.about')->name('about');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/guide', 'pages.guide')->name('guide');

// ── حسابات العائلات ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [FamilyAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [FamilyAuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [FamilyAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [FamilyAuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::post('/logout', [FamilyAuthController::class, 'logout'])->middleware('auth')->name('logout');

// ── حساب وليّ الأمر وحجز المقاعد ──
Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    Route::post('/account/children', [ChildController::class, 'store'])->name('children.store');
    Route::put('/account/children/{child}', [ChildController::class, 'update'])->name('children.update');
    Route::delete('/account/children/{child}', [ChildController::class, 'destroy'])->name('children.destroy');

    Route::get('/account/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/account/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
    Route::delete('/account/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.clear');

    // ── لوحة الفريق المنظِّم ──
    Route::middleware(EnsureTeamManager::class)->group(function () {
        Route::get('/organizer', [OrganizerDashboardController::class, 'dashboard'])->name('organizer.dashboard');
        Route::get('/organizer/events', [OrganizerDashboardController::class, 'events'])->name('organizer.events');

        Route::get('/organizer/events/create', [OrganizerEventController::class, 'create'])->name('organizer.events.create');
        Route::post('/organizer/events', [OrganizerEventController::class, 'store'])->name('organizer.events.store');
        Route::get('/organizer/events/{event}/edit', [OrganizerEventController::class, 'edit'])->name('organizer.events.edit');
        Route::put('/organizer/events/{event}', [OrganizerEventController::class, 'update'])->name('organizer.events.update');
        Route::delete('/organizer/events/{event}', [OrganizerEventController::class, 'destroy'])->name('organizer.events.destroy');

        Route::get('/organizer/events/{event}/attendance', [OrganizerAttendanceController::class, 'create'])->name('organizer.events.attendance');
        Route::post('/organizer/events/{event}/attendance', [OrganizerAttendanceController::class, 'store'])->name('organizer.events.attendance.store');
    });

    Route::get('/my-events', [RegistrationController::class, 'index'])->name('my-events');
    Route::post('/events/{event}/register', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::delete('/registrations/{registration}', [RegistrationController::class, 'destroy'])->name('registrations.destroy');
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
