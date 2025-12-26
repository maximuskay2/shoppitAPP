<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\Vendor;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory, UUID;
    
    protected $table = 'settlements'; 

    protected $guarded = [];

    protected $casts = [
        'total_amount' => TXAmountCast::class,
        'vendor_amount' => TXAmountCast::class,
        'platform_fee' => TXAmountCast::class,
        'settled_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
