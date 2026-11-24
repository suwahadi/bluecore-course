<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $paid = Order::query()->where('status', OrderStatus::Paid);
        $paidCount = (clone $paid)->count();
        $revenue = (int) (clone $paid)->sum('amount');

        return view('admin.dashboard', [
            'revenue' => $revenue,
            'paidCount' => $paidCount,
            'monthRevenue' => (int) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'averageOrder' => $paidCount ? intdiv($revenue, $paidCount) : 0,
            'todayOrders' => Order::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'pendingCount' => Order::query()->where('status', OrderStatus::Pending)->count(),
            'publishedCourses' => Course::query()->where('status', CourseStatus::Published)->count(),
            'draftCourses' => Course::query()->where('status', CourseStatus::Draft)->count(),
            'userCount' => User::query()->count(),
            'buyerCount' => (clone $paid)->distinct()->count('user_id'),
            'pendingOrders' => Order::query()
                ->where('status', OrderStatus::Pending)
                ->with(['user:id,name,email', 'course:id,title'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
