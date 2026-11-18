<?php

namespace App\Models;

use App\Enums\PaymentCallbackStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'merchant_order_id', 'duitku_reference', 'result_code', 'amount', 'event_key',
    'signature_valid', 'payload', 'status', 'attempts', 'last_error', 'processed_at',
])]
class PaymentCallback extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'received',
        'attempts' => 0,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'status' => PaymentCallbackStatus::class,
            'attempts' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
