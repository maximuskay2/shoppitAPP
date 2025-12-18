<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory, UUID;

    protected $table = 'cart_items';

    protected $guarded = [];

    protected $casts = [
        'price' => TXAmountCast::class,
        'subtotal' => TXAmountCast::class,
    ];

    public function cartVendor()
    {
        return $this->belongsTo(CartVendor::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}