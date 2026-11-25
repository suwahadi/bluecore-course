<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkPaidRequest;
use App\Http\Requests\Admin\RefundRequest;
use App\Http\Requests\OrderDateFilterRequest;
use App\Models\Order;
use App\Services\Orders\OrderService;
use App\Services\Payments\DuitkuClient;
use App\Services\Payments\DuitkuException;
use App\Services\Payments\PaymentCallbackService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public const PER_PAGE = 20;

    public const MARKABLE = [OrderStatus::Pending, OrderStatus::Expired, OrderStatus::Cancelled, OrderStatus::Failed];

    public function __construct(private OrderService $orders, private DuitkuClient $duitku, private PaymentCallbackService $payments)
    {
    }

    public function index(OrderDateFilterRequest $request): View
    {
        $term = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '';
        $status = OrderStatus::tryFrom(is_string($request->query('status')) ? $request->query('status') : '');
        $from = $request->from();
        $to = $request->to();

        $orders = Order::query()
            ->with(['user:id,name,email', 'course:id,title'])
            ->when($term !== '', function (Builder $query) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn (Builder $query) => $query
                    ->where('order_number', 'like', $like)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->createdBetween($from, $to)
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'term' => $term,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'filtered' => $term !== '' || $status !== null || $from !== null || $to !== null,
        ]);
    }

    public function markPaid(MarkPaidRequest $request, Order $order): RedirectResponse
    {
        $marked = in_array($order->status, self::MARKABLE, true)
            && $this->orders->markPaid($order, null, null, $request->user(), $request->validated('paid_note'));

        if (! $marked) {
            return back()->with('error', 'Pesanan '.$order->order_number.' sudah lunas atau dananya sudah dikembalikan.');
        }

        return back()->with('status', 'Pesanan '.$order->order_number.' ditandai lunas. Akses dibuka dan email konfirmasi terkirim ke pembeli.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        if (! $this->orders->cancel($order)) {
            return back()->with('error', 'Hanya pesanan yang menunggu pembayaran yang bisa dibatalkan.');
        }

        return back()->with('status', 'Pesanan '.$order->order_number.' dibatalkan.');
    }

    public function checkStatus(Order $order): RedirectResponse
    {
        try {
            $status = $this->duitku->transactionStatus($order->order_number);
        } catch (DuitkuException) {
            return back()->with('error', 'Gagal menghubungi Duitku. Coba lagi.');
        }

        $number = $order->order_number;
        $message = match ($status['status_code']) {
            '00' => $this->payments->applyConfirmedPayment($order, $status, null)
                ? 'Duitku mengonfirmasi pembayaran. Pesanan '.$number.' ditandai lunas.'
                : 'Duitku mencatat pesanan '.$number.' lunas. Status di aplikasi tidak berubah.',
            '01' => 'Duitku: pesanan '.$number.' masih menunggu pembayaran.',
            default => 'Duitku: pembayaran pesanan '.$number.' gagal atau kedaluwarsa.',
        };

        return back()->with('status', $message);
    }

    public function refund(RefundRequest $request, Order $order): RedirectResponse
    {
        if (! $this->orders->refund($order, $request->user(), $request->validated('refund_note'))) {
            return back()->with('error', 'Hanya pesanan lunas yang bisa dicatat pengembalian dananya.');
        }

        return back()->with('status', 'Pengembalian dana pesanan '.$order->order_number.' dicatat. Akses kursus ditutup.');
    }
}
