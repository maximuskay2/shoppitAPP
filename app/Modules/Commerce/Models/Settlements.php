<?php

namespace App\Modules\Commerce\Models;

use App\Modules\User\Models\Vendor;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlements extends Model
{
    use HasFactory, UUID;
    
    protected $table = 'settlements'; 

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
