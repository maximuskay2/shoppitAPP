<?php

namespace App\Modules\Transaction\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';

    public static function toArray(): array
    {
        return array_column(SubscriptionStatusEnum::cases(), 'value');
    }
}
