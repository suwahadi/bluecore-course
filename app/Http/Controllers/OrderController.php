<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\OrderDateFilterRequest;
use App\Models\Order;
use App\Services\Orders\OrderService;
use App\Services\Payments\DuitkuClient;
use App\Services\Payments\DuitkuException;
use App\Services\Payments\DuitkuTransactionNotFound;
use App\Services\Payments\PaymentCallbackService;
use App\Support\Tracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    public const PAGE_CHECK_SECONDS = 30;

    public const REFRESH_SECONDS = 5;

    public const PER_PAGE = 20;

    public function __construct(private OrderService $orders, private DuitkuClient $duitku, private PaymentCallbackService $payments)
    {
    }

    public function index(OrderDateFilterRequest $request): View
    {
        $from = $request->from();
        $to = $request->to();
        $mine = Order::query()->where('user_id', $request->user()->id);

        return view('orders.index', [
            'orders' => (clone $mine)->createdBetween($from, $to)->with('course:id,title')->latest()->latest('id')->paginate(self::PER_PAGE)->withQueryString(),
            'hasOrders' => (clone $mine)->exists(),
            'filtered' => $from !== null || $to !== null,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeView($order);

        if ($order->status === OrderStatus::Pending) {
            $this->syncWithDuitku($order, 'order-page-check:'.$order->id, self::PAGE_CHECK_SECONDS);
        }

        $order->load(['user:id,name', 'course.category', 'course.materials:id,course_id,title', 'confirmedBy:id,name'])
            ->course->loadCount(['segments', 'materials'])->loadSum('segments', 'duration_seconds');

        $isOwner = $order->user_id === $request->user()->id;

        if ($request->user()->isAdmin()) {
            $order->load(['callbacks' => fn ($query) => $query->latest()->latest('id')]);
        }

        if ($isOwner && $order->status === OrderStatus::Paid && $order->purchase_tracked_at === null) {
            $order->forceFill(['purchase_tracked_at' => now()])->save();
            Tracking::push('purchase', [
                'transaction_id' => $order->order_number,
                'currency' => 'IDR',
                'value' => $order->amount,
                'items' => [['item_id' => (string) $order->course_id, 'item_name' => $order->course->title]],
            ]);
        }

        return view('orders.show', [
            'order' => $order,
            'isOwner' => $isOwner,
            'paymentName' => $this->duitku->paymentName($order->duitku_payment_code),
            'autoOpen' => $request->boolean('pay'),
        ]);
    }

    public function status(Order $order): JsonResponse
    {
        $this->authorizeView($order);

        return response()->json(['status' => $order->status->value]);
    }

    public function refresh(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $this->authorizeView($order);

        if ($order->status === OrderStatus::Pending) {
            $this->syncWithDuitku($order, 'order-refresh:'.$order->id, self::REFRESH_SECONDS);
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => $order->status->value]);
        }

        return back()->with('status', $order->status === OrderStatus::Paid
            ? 'Pembayaran diterima. Akses kursus sudah terbuka.'
            : 'Pembayaran belum diterima. Coba lagi beberapa saat lagi.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeView($order);

        if (! $this->orders->cancel($order)) {
            return back()->with('error', 'Pesanan '.$order->order_number.' sudah tidak menunggu pembayaran.');
        }

        return back()->with('status', 'Pesanan '.$order->order_number.' dibatalkan.');
    }

    private function authorizeView(Order $order): void
    {
        abort_unless(Gate::allows('view', $order), 403, 'Pesanan ini milik akun lain.');
    }

    private function syncWithDuitku(Order $order, string $lockKey, int $seconds): void
    {
        if (! Cache::lock($lockKey, $seconds)->get()) {
            return;
        }

        try {
            $status = $this->duitku->transactionStatus($order->order_number);
        } catch (DuitkuTransactionNotFound) {
            return;
        } catch (DuitkuException $exception) {
            Log::warning('Duitku status check failed', ['order_number' => $order->order_number, 'reason' => $exception->getMessage()]);

            return;
        }

        $this->payments->applyConfirmedPayment($order, $status, null);
    }
}
