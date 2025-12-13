<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Transaction\Enums\UserSubscriptionStatusEnum;
use App\Modules\User\Models\Vendor;
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
        'status' => UserSubscriptionStatusEnum::class,
        'benefits_suspended' => 'boolean',
    ];

    protected $hidden = [
      'card_token_key',
      'paystack_subscription_code',
      'paystack_customer_code'  
    ];

    public function records()
    {
        return $this->hasMany(SubscriptionRecord::class);
    }
    
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');

    }

}
