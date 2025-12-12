<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use UUID;

    protected $table = 'subscription_plans';
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'amount' => TXAmountCast::class,
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
