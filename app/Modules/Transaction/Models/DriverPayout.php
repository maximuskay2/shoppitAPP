<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverPayout extends Model
{
    use HasFactory, UUID;

    protected $guarded = [];

    protected $casts = [
        'amount' => TXAmountCast::class,
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(DriverEarning::class, 'payout_id');
    }
}
