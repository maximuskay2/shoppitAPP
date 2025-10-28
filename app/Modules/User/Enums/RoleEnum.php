<?php

namespace App\Modules\User\Enums;

enum RoleEnum: string
{
    case CUSTOMER = 'CUSTOMER';
    case VENDOR = 'VENDOR';

    public static function toArray(): array
    {
        return array_column(RoleEnum::cases(), 'value');
    }
}
