<?php

namespace App\Modules\Transaction\Enums;

enum PartnersEnum: string
{
    case PAYSTACK = 'paystack';
    case QOREID = 'qoreid';

    public static function toArray(): array
    {
        return array_column(PartnersEnum::cases(), 'value');
    }
}
