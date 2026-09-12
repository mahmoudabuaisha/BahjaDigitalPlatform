<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\FamilyAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventIndexController;
use App\Http\Controllers\EventQrController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NeighbourhoodCallController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizerAttendanceController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\OrganizerDashboardController;
use App\Http\Controllers\OrganizerEventController;
use App\Http\Controllers\OrganizerRegistrationController;
use App\Http\Controllers\OrganizerTeamProfileController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamApplicationController;
use App\Http\Controllers\TeamPublicController;
use App\Http\Controllers\TrackController;
use App\Http\Middleware\EnsureTeamManager;
use Illuminate\Http\RedirectResponse;
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
// لوحة الفرق صارت واحدة (/organizer): أي وصول للمسار القديم يُحوَّل إليها،
// والتسجيل الذاتي القديم يُحوَّل إلى نموذج الانضمام كي يمرّ كل فريق باعتماد الإدارة
Route::redirect('/team/register', '/join-team');
Route::get('/team/{any?}', fn (): RedirectResponse => redirect()->route('organizer.dashboard'))
    ->where('any', '.*');

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
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/photo-policy', 'pages.photo-policy')->name('photo-policy');

// ── حسابات العائلات ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [FamilyAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [FamilyAuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [FamilyAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [FamilyAuthController::class, 'register'])->middleware('throttle:6,1');

    // استعادة كلمة المرور بالبريد
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'form'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [FamilyAuthController::class, 'logout'])->middleware('auth')->name('logout');

// ── تأكيد البريد الإلكتروني — لافتة ودّية، لا بوابة تقفل الحجز ──
Route::middleware('auth')->group(function () {
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// ── حساب وليّ الأمر وحجز المقاعد ──
Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    Route::post('/account/children', [ChildController::class, 'store'])->name('children.store');
    Route::put('/account/children/{child}', [ChildController::class, 'update'])->name('children.update');
    Route::delete('/account/children/{child}', [ChildController::class, 'destroy'])->name('children.destroy');

    // نداء الحيّ: «لا نجد فعالية قريبة منّا»
    Route::post('/account/call', [NeighbourhoodCallController::class, 'store'])
        ->middleware('throttle:feedback')->name('calls.store');
    Route::delete('/account/call', [NeighbourhoodCallController::class, 'destroy'])->name('calls.destroy');

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

        // تسجيلات العائلات: الفريق يقبل أو يعتذر بنفسه
        Route::get('/organizer/events/{event}/registrations', [OrganizerRegistrationController::class, 'index'])->name('organizer.events.registrations');
        Route::post('/organizer/events/{event}/registrations/{registration}/accept', [OrganizerRegistrationController::class, 'accept'])->name('organizer.registrations.accept');
        Route::post('/organizer/events/{event}/registrations/{registration}/reject', [OrganizerRegistrationController::class, 'reject'])->name('organizer.registrations.reject');

        // ملف الفريق: بيانات الفريق وشعاره كما تراها العائلات
        Route::get('/organizer/profile', [OrganizerTeamProfileController::class, 'edit'])->name('organizer.profile');
        Route::put('/organizer/profile', [OrganizerTeamProfileController::class, 'update'])->name('organizer.profile.update');
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
Route::view('/app', 'pages.install')->name('install');
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
