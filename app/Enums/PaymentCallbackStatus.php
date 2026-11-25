<?php

namespace App\Enums;

enum PaymentCallbackStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Ignored = 'ignored';
    case Rejected = 'rejected';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Diterima',
            self::Processed => 'Diproses',
            self::Ignored => 'Diabaikan',
            self::Rejected => 'Ditolak',
            self::Failed => 'Gagal',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Processed, self::Ignored, self::Rejected], true);
    }
}
