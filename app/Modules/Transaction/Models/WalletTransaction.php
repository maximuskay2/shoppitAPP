<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory, UUID;

    protected $guarded = [];

    protected $casts = [
        'previous_balance' => TXAmountCast::class,
        'new_balance'  => TXAmountCast::class,
        'amount_change' => TXAmountCast::class,
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
