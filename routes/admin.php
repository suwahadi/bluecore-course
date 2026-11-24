<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SegmentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');
Route::post('categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');

Route::resource('testimonials', TestimonialController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('testimonials/{testimonial}/move', [TestimonialController::class, 'move'])->name('testimonials.move');
Route::post('testimonials/{testimonial}/toggle', [TestimonialController::class, 'toggle'])->name('testimonials.toggle');

Route::resource('pages', PageController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('pages/{page}/move', [PageController::class, 'move'])->name('pages.move');
Route::post('pages/{page}/toggle', [PageController::class, 'toggle'])->name('pages.toggle');

Route::resource('courses', CourseController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
Route::post('courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
Route::put('courses/{course}/segments', [SegmentController::class, 'update'])->name('courses.segments.update');

Route::scopeBindings()->group(function () {
    Route::post('courses/{course}/materials', [MaterialController::class, 'store'])->name('courses.materials.store');
    Route::put('courses/{course}/materials/{material}', [MaterialController::class, 'update'])->name('courses.materials.update');
    Route::post('courses/{course}/materials/{material}/move', [MaterialController::class, 'move'])->name('courses.materials.move');
    Route::delete('courses/{course}/materials/{material}', [MaterialController::class, 'destroy'])->name('courses.materials.destroy');
});

Route::get('users/export', UserExportController::class)->middleware('throttle:5,1,users-export')->name('users.export');
Route::get('users', [UserController::class, 'index'])->name('users.index');
Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
Route::delete('users/{user}/personal-data', [UserController::class, 'anonymize'])->name('users.anonymize');
Route::post('users/{user}/enrollments', [EnrollmentController::class, 'store'])->name('users.enrollments.store');

Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
