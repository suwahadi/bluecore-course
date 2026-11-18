<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'course_id', 'subtotal', 'admin_fee', 'amount', 'status', 'duitku_payment_code',
    'duitku_reference', 'payment_url', 'expires_at', 'paid_at', 'paid_note', 'purchase_tracked_at',
    'refunded_at', 'refund_note',
])]
class Order extends Model
{
    use HasFactory;

    private const BUSINESS_TIMEZONE = 'Asia/Jakarta';

    protected $attributes = [
        'status' => 'pending',
        'admin_fee' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->uuid ??= (string) Str::uuid();
            $order->order_number ??= static::newOrderNumber();
        });
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'admin_fee' => 'integer',
            'amount' => 'integer',
            'status' => OrderStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'purchase_tracked_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public static function newOrderNumber(): string
    {
        do {
            $number = Str::upper(Str::random(11));
        } while (static::query()->where('order_number', $number)->exists());

        return $number;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by_user_id');
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(PaymentCallback::class);
    }

    public function scopeCreatedBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $this->startOfBusinessDay($from)))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $this->endOfBusinessDay($to)));
    }

    private function startOfBusinessDay(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date, self::BUSINESS_TIMEZONE)->startOfDay()->timezone(config('app.timezone'));
    }

    private function endOfBusinessDay(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date, self::BUSINESS_TIMEZONE)->endOfDay()->timezone(config('app.timezone'));
    }
}
