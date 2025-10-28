<?php

namespace App\Modules\User\Models;

use App\Modules\Commerce\Models\Food;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Review;
use App\Modules\Commerce\Models\Settlements;
use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $table = 'vendors'; 

    protected $guarded = [];

    protected $casts = [
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'kyb_status' => UserKYBStatusEnum::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentDetails()
    {
        return $this->hasOne(PaymentDetails::class);
    }   

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function averageRating()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function foodItems(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlements::class);
    }
}
