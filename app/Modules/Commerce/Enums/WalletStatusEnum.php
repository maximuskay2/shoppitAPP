<?php

namespace App\Modules\Blockchain\Enums;

enum WalletStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';

    public static function toArray(): array
    {
        return array_column(WalletStatusEnum::cases(), 'value');
    }
}