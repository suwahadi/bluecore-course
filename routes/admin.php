<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SegmentController;
use App\Http\Controllers\Admin\TestimonialController;
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
