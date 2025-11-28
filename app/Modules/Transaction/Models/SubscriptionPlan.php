<?php

namespace App\Modules\Transaction\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use UUID;

    protected $table = 'subscription_plans';
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
