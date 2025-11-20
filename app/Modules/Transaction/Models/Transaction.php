<?php

namespace App\Modules\Transaction\Models;

use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use UUID;

    protected $table = 'transactions';
    protected $guarded = [];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the transaction's ID.
     *
     * @return string
     */
    public function getIdAttribute(): string
    {
        return $this->attributes['id'] ?? 'Unknown Transaction';
    }
    
    /**
     * Get the transaction's amount.
     *
     * @return float
     */
    public function getAmountAttribute(): float
    {
        return $this->attributes['amount'] ?? 0.0;
    }
}
