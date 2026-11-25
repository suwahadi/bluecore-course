<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminOrderPaidNotification;
use App\Notifications\OrderExpiredNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Services\Enrollments\EnrollmentService;
use App\Support\Settings;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrderService
{
    public function __construct(private EnrollmentService $enrollments)
    {
    }

    public function createOrder(User $user, Course $course): ?Order
    {
        return DB::transaction(function () use ($user, $course) {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if (Enrollment::query()->where(['user_id' => $user->id, 'course_id' => $course->id])->exists()) {
                return null;
            }

            $open = $this->openOrder($user, $course);

            if ($open) {
                return $open;
            }

            $adminFee = (int) Settings::get('admin_fee');

            return Order::query()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'subtotal' => $course->price,
                'admin_fee' => $adminFee,
                'amount' => $course->price + $adminFee,
                'status' => OrderStatus::Pending,
                'expires_at' => now()->addMinutes((int) config('duitku.expiry_minutes')),
            ]);
        });
    }

    public function openOrder(User $user, Course $course): ?Order
    {
        return Order::query()
            ->where(['user_id' => $user->id, 'course_id' => $course->id, 'status' => OrderStatus::Pending])
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function markPaid(Order $order, ?string $paymentCode, ?string $reference, ?User $confirmedBy = null, ?string $note = null): bool
    {
        $paid = DB::transaction(function () use ($order, $paymentCode, $reference, $confirmedBy, $note) {
            $locked = $this->lock($order);

            if (in_array($locked->status, [OrderStatus::Paid, OrderStatus::Refunded], true)) {
                return false;
            }

            $locked->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'duitku_payment_code' => $paymentCode ?? $locked->duitku_payment_code,
                'duitku_reference' => $reference ?? $locked->duitku_reference,
                'confirmed_by_user_id' => $confirmedBy?->id,
                'paid_note' => $note,
            ])->save();

            $this->enrollments->grant($locked->user, $locked->course, $locked);
            $locked->user->notify(new PaymentSucceededNotification($locked));

            if ($confirmedBy === null) {
                Notification::send($this->verifiedAdmins(), new AdminOrderPaidNotification($locked));
            } else {
                Log::info('Admin marked order paid', ['order_number' => $locked->order_number, 'admin_id' => $confirmedBy->id]);
            }

            return true;
        });

        $order->refresh();

        return $paid;
    }

    public function cancel(Order $order): bool
    {
        return $this->leavePending($order, OrderStatus::Cancelled);
    }

    public function fail(Order $order): bool
    {
        return $this->leavePending($order, OrderStatus::Failed);
    }

    public function expire(Order $order): bool
    {
        $expired = $this->leavePending($order, OrderStatus::Expired, fn (Order $locked) => $locked->expires_at !== null && $locked->expires_at->isPast());

        if ($expired) {
            $order->user->notify(new OrderExpiredNotification($order));
        }

        return $expired;
    }

    public function refund(Order $order, User $admin, string $note): bool
    {
        $refunded = DB::transaction(function () use ($order, $admin, $note) {
            $locked = $this->lock($order);

            if ($locked->status !== OrderStatus::Paid) {
                return false;
            }

            $locked->forceFill([
                'status' => OrderStatus::Refunded,
                'refunded_at' => now(),
                'refunded_by_user_id' => $admin->id,
                'refund_note' => $note,
            ])->save();

            Enrollment::query()->where('order_id', $locked->id)->delete();
            Log::info('Admin recorded refund', ['order_number' => $locked->order_number, 'admin_id' => $admin->id]);

            return true;
        });

        $order->refresh();

        return $refunded;
    }

    private function leavePending(Order $order, OrderStatus $status, ?Closure $allowed = null): bool
    {
        $changed = DB::transaction(function () use ($order, $status, $allowed) {
            $locked = $this->lock($order);

            if ($locked->status !== OrderStatus::Pending || ($allowed && ! $allowed($locked))) {
                return false;
            }

            $locked->forceFill(['status' => $status])->save();

            return true;
        });

        $order->refresh();

        return $changed;
    }

    private function lock(Order $order): Order
    {
        return Order::query()->with(['user', 'course'])->whereKey($order->id)->lockForUpdate()->firstOrFail();
    }

    private function verifiedAdmins()
    {
        return User::query()->where('role', UserRole::Admin)->whereNotNull('email_verified_at')->get();
    }
}
