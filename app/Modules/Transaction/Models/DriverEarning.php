<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Commerce\Models\Order;
use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverEarning extends Model
{
    use HasFactory, UUID;

    protected $guarded = [];

    protected $casts = [
        'gross_amount' => TXAmountCast::class,
        'commission_amount' => TXAmountCast::class,
        'net_amount' => TXAmountCast::class,
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(DriverPayout::class, 'payout_id');
    }
}
