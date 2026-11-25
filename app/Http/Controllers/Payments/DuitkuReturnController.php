<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DuitkuReturnController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $number = $request->query('merchantOrderId');

        $order = is_string($number)
            ? Order::query()->where(['order_number' => $number, 'user_id' => $request->user()->id])->first()
            : null;

        return $order ? redirect()->route('order.show', $order) : redirect()->route('orders.index');
    }
}
