<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLineItems extends Model
{
    use HasFactory, UUID;
    
    protected $table = 'order_line_items'; 

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
