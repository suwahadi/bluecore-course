<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Notifications\OrderCreatedNotification;
use App\Services\Orders\OrderService;
use App\Services\Payments\DuitkuClient;
use App\Services\Payments\DuitkuException;
use App\Support\Settings;
use App\Support\Tracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private OrderService $orders, private DuitkuClient $duitku)
    {
    }

    public function show(Request $request, Course $course): View|RedirectResponse
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        $user = $request->user();

        if ($user->can('learn', $course)) {
            return redirect()->to(url('/learn/'.$course->slug));
        }

        if ($open = $this->orders->openOrder($user, $course)) {
            return redirect()->to(url('/order/'.$open->uuid));
        }

        $course->load('category')->loadCount(['segments', 'materials'])->loadSum('segments', 'duration_seconds');
        $adminFee = (int) Settings::get('admin_fee');

        Tracking::push('begin_checkout', [
            'currency' => 'IDR',
            'value' => $course->price + $adminFee,
            'items' => [['item_id' => (string) $course->id, 'item_name' => $course->title]],
        ]);

        return view('checkout.show', [
            'course' => $course,
            'adminFee' => $adminFee,
            'total' => $course->price + $adminFee,
            'deadline' => $this->deadlineText((int) config('duitku.expiry_minutes')),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        $user = $request->user();
        $order = $this->orders->createOrder($user, $course);

        if ($order === null) {
            return redirect()->to(url('/learn/'.$course->slug));
        }

        if ($order->duitku_reference === null) {
            try {
                $invoice = $this->duitku->createInvoice($order);
            } catch (DuitkuException $exception) {
                $this->orders->fail($order);
                Log::warning('Duitku invoice failed', ['order_number' => $order->order_number, 'reason' => $exception->getMessage()]);

                return redirect()->route('checkout.show', $course)->with('error', 'Gagal membuat tagihan pembayaran. Coba lagi.');
            }

            $order->forceFill(['duitku_reference' => $invoice['reference'], 'payment_url' => $invoice['payment_url']])->save();
            $user->notify(new OrderCreatedNotification($order));
        }

        return redirect()->to(url('/order/'.$order->uuid.'?pay=1'));
    }

    private function deadlineText(int $minutes): string
    {
        return $minutes % 60 === 0 ? ($minutes / 60).' jam' : $minutes.' menit';
    }
}
