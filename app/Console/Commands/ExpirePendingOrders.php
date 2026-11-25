<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Menandai pesanan pending yang lewat batas waktu sebagai kedaluwarsa';

    public function handle(OrderService $orders): int
    {
        $expired = 0;

        Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($batch) use ($orders, &$expired) {
                foreach ($batch as $order) {
                    $expired += $orders->expire($order) ? 1 : 0;
                }
            });

        $this->info($expired.' pesanan ditandai kedaluwarsa.');

        return self::SUCCESS;
    }
}
