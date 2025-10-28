<?php

namespace App\Modules\Blockchain\Enums;

enum TransactionStatusEnum: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case FAILED = 'FAILED';

    public static function toArray(): array
    {
        return array_column(TransactionStatusEnum::cases(), 'value');
    }
}