<?php

namespace App\Modules\Transaction\Enums;

enum SubscriptionRecordStatusEnum: string
{
    case SUCCESSFUL = 'SUCCESSFUL';
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';

    public static function toArray(): array
    {
        return array_column(SubscriptionRecordStatusEnum::cases(), 'value');
    }
}
