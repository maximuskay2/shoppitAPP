<?php

namespace App\Modules\Commerce\Models;

use App\Modules\User\Models\Vendor;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class CartVendor extends Model
{
    use UUID;

    protected $table = 'cart_vendors';
    protected $guarded = [];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function total()
    {
        return $this->items->sum(function ($item) {
            return $item->subtotal->getAmount()->toFloat();
        });
    }
}
