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

    public function vendors()
    {
        return $this->hasMany(CartVendor::class);
    }

    public function items()
    {
        return $this->hasManyThrough(CartItem::class, CartVendor::class);
    }

    public function total()
    {
        return $this->vendors->sum(function ($vendor) {
            return $vendor->total();
        });
    }
}