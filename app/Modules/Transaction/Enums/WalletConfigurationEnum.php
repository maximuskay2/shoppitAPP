<?php

namespace App\Modules\Blockchain\Enums;

enum WalletConfigurationEnum: string
{
    case HOT = 'HOT';
    case WARM = 'WARM';
    case COLD = 'COLD';
    case DEPOSIT = 'DEPOSIT';
    case ESCROW = 'ESCROW';

    public static function toArray(): array
    {
        return array_column(WalletConfigurationEnum::cases(), 'value');
    }
}