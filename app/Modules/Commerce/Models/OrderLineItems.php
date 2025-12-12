<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLineItems extends Model
{
    use HasFactory, UUID;
    
    protected $table = 'order_line_items'; 

    protected $guarded = [];

    protected $casts = [
        'price' => TXAmountCast::class,
        'subtotal' => TXAmountCast::class,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
