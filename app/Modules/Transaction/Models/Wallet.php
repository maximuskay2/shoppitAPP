<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = 'wallets';

    protected $guarded = [];

    protected $casts = [
        'amount' => TXAmountCast::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the wallet's balance.
     *
     * @return int
     */
    public function getBalanceAttribute(): float
    {
        return $this->amount->getAmount()->toFloat();
    }
    
    public function getCurrencyAttribute(): string
    {
        return $this->attributes['currency'] ?? 'NGN';
    }

    public function getActiveAttribute(): string
    {
        return $this->attributes['is_status'] === true;
    }

    public function getTransactionCountAttribute(): int
    {
        return $this->transactions()->count();
    }
}
