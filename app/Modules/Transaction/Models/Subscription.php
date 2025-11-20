<?php

namespace App\Modules\Transaction\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use UUID;

    protected $table = 'subscriptions';
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'payment_failed_at' => 'datetime',
        'last_failure_notification_at' => 'datetime',
        'is_active' => 'boolean',
        'benefits_suspended' => 'boolean',
    ];

    public function records()
    {
        return $this->hasMany(SubscriptionRecord::class);
    }
    
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

}
