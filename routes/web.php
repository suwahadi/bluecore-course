<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FreeVideoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MaterialDownloadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Payments\DuitkuCallbackController;
use App\Http\Controllers\Payments\DuitkuReturnController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\EnsureScalarAuthInput;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

Route::get('/', HomeController::class)->name('home');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/contact', ContactController::class)->name('contact');
Route::get('/free-video', FreeVideoController::class)->name('free-video');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::post('/payments/duitku/callback', DuitkuCallbackController::class)->middleware('throttle:60,1,duitku-callback')->name('payments.duitku.callback');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::middleware(['guest', EnsureScalarAuthInput::class])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1,register')->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:3,1,forgot-password')->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1,reset-password')->name('password.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/courses/{course:slug}/checkout', [CheckoutController::class, 'show'])->middleware('throttle:20,1,checkout-show')->name('checkout.show');
    Route::post('/courses/{course:slug}/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1,checkout')->name('checkout.store');
    Route::get('/courses/{course:slug}/materials/{material}', MaterialDownloadController::class)->scopeBindings()->name('materials.download');
});

Route::middleware(['auth', EnsureScalarAuthInput::class])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/payments/duitku/return', DuitkuReturnController::class)->name('payments.duitku.return');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/order/{order}', [OrderController::class, 'show'])->name('order.show');
    Route::get('/order/{order}/status', [OrderController::class, 'status'])->middleware('throttle:60,1,order-status')->name('order.status');
    Route::post('/order/{order}/refresh', [OrderController::class, 'refresh'])->middleware('throttle:10,1,order-action')->name('order.refresh');
    Route::post('/order/{order}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:10,1,order-action')->name('order.cancel');
    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1,verify-email'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1,verification-send')->name('verification.send');

    Route::view('/account', 'account.show')->name('account');
    Route::put('/user/profile-information', [ProfileInformationController::class, 'update'])->middleware('throttle:6,1,profile')->name('user-profile-information.update');
    Route::put('/user/password', [PasswordController::class, 'update'])->middleware('throttle:6,1,password')->name('user-password.update');
});

Route::get('/{page:slug}', [PageController::class, 'show'])->where('page', Page::slugRoutePattern())->name('page.show');

Route::fallback(fn () => abort(404));
