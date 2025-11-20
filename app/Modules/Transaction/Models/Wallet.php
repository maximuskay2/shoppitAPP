<?php

namespace App\Modules\Transaction\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = 'wallets';

    protected $guarded = [];

    protected $casts = [
        'balance' => 'integer',
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
    public function getBalanceAttribute(): int
    {
        return $this->attributes['balance'] ?? 0;
    }
    
    public function getCurrencyAttribute(): string
    {
        return $this->attributes['currency'] ?? 'NGN';
    }

    public function getActiveAttribute(): string
    {
        return $this->attributes['status'] === 'active';
    }

    public function getTransactionCountAttribute(): int
    {
        return $this->transactions()->count();
    }
}
