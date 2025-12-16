<?php

namespace App\Modules\Commerce\Models;

use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory, UUID;

    protected $table = 'carts';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function total()
    {
        return $this->items()->sum(
            function ($item) {
                return $item->subtotal->getAmount()->toFloat();
            }
        );
    }
}