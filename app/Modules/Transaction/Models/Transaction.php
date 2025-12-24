<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory, UUID;

    protected $table = 'transactions';
    protected $guarded = [];

    protected $casts = [
        'amount' => TXAmountCast::class,
    ];

    const WALLET_FUNDING_FEE = 10.0;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Get the principal transaction this fee belongs to
     */
    public function principalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'principal_transaction_id');
    }

    /**
     * Get all fee transactions associated with this principal transaction
     */
    public function feeTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'principal_transaction_id'); // Optional: scope to just fees
    }

    /**
     * Get all related transactions (both principal and fees)
     */
    public function relatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'principal_transaction_id');
    }

    /**
     * Scope to only include fee transactions
     */
    public function scopeFees($query)
    {
        return $query->where('type', 'FUND_WALLET_FEE')->orWhere('type', 'WITHDRAW_WALLET_FEE');
    }

    /**
     * Scope to only include principal transactions
     */
    public function scopePrincipal($query)
    {
        return $query->whereNull('principal_transaction_id');
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

    /**
     * Check if the transaction is a fund wallet type
     *
     * @return boolean
     */
    public function isFundWalletTransaction()
    {
        return $this->type == "FUND_WALLET";
    }


}
