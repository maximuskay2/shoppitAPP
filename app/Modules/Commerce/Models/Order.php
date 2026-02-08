<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use App\Traits\UUID;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, UUID;
    
    protected $table = 'orders'; 

    protected $guarded = [];

    const COMMISSION_RATE = 15; // 15%

    protected $casts = [
        'coupon_discount' => TXAmountCast::class,
        'delivery_fee' => TXAmountCast::class,
        'gross_total_amount' => TXAmountCast::class,
        'net_total_amount' => TXAmountCast::class,
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function lineItems()
    {
        return $this->hasMany(OrderLineItems::class);
    }

    public function driverEarning()
    {
        return $this->hasOne(DriverEarning::class, 'order_id');
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
