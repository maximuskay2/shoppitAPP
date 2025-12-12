<?php

namespace App\Modules\Transaction\Enums;

enum UserSubscriptionStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case PENDING = 'PENDING';
    case CANCELLED = 'CANCELLED';
    case EXPIRED = 'EXPIRED';

    public static function toArray(): array
    {
        return array_column(UserSubscriptionStatusEnum::cases(), 'value');
    }
}
