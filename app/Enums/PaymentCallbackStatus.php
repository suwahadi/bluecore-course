<?php

namespace App\Enums;

enum PaymentCallbackStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Ignored = 'ignored';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
