<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    use HasFactory, UUID;

    protected $table = 'coupon_usages';

    protected $guarded = [];

    protected $casts = [
        'discount_amount' => TXAmountCast::class,
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}