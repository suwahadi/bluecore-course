<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');
Route::post('categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');

Route::resource('testimonials', TestimonialController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('testimonials/{testimonial}/move', [TestimonialController::class, 'move'])->name('testimonials.move');
Route::post('testimonials/{testimonial}/toggle', [TestimonialController::class, 'toggle'])->name('testimonials.toggle');
