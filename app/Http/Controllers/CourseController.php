<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        return view('courses.index');
    }

    public function show(Course $course): never
    {
        abort(404);
    }
}
