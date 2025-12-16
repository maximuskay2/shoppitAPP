<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Models\User;
use Illuminate\Support\Str;

class WalletService
{
    public $currency;

    public function __construct() {
        $this->currency = Settings::where('name', 'currency')->first()->value;
    }

    public function create(User $user): void
    {
        if ($user->wallet) {
            return;
        }
        
        $user->wallet()->create([
            'id' => Str::uuid(),
            'amount' => 0,
            'currency' => $this->currency,
            'is_active' => true,
        ]);
    }
}