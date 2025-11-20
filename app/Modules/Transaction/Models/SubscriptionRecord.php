<?php

namespace App\Modules\Transaction\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class SubscriptionRecord extends Model
{
    use UUID;

    protected $table = 'subscription_records';
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
    
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
