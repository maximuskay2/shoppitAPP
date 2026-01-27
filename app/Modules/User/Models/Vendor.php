<?php

namespace App\Modules\User\Models;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\ProductCategory;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Promotion;
use App\Modules\Commerce\Models\Review;
use App\Modules\Commerce\Models\Settlement;
use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\Transaction\Models\Subscription;
use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class Vendor extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $table = 'vendors'; 

    protected $guarded = [];

    protected $casts = [
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'kyb_status' => UserKYBStatusEnum::class,
        'delivery_fee' => TXAmountCast::class,
    ];

    protected $hidden = [
        'tin',
        'cac'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function averageRating()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function favourites()
    {
        return $this->morphMany(Favourite::class, 'favouritable');
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(PaymentDetail::class);
    }

    public function isKybVerified(): bool
    {
        return $this->kyb_status === UserKYBStatusEnum::SUCCESSFUL;
    }

    public function scopeWithCac($query, $cac)
    {
        return $query->whereIn('id', Vendor::where('kyb_status', UserKYBStatusEnum::SUCCESSFUL)->get()
            ->filter(function($user) use ($cac) {
                try {
                    return Crypt::decryptString($user->cac) === $cac;
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    // Log the error for debugging
                    Log::warning("Failed to decrypt CAC for user {$user->id}: " . $e->getMessage());
                    return false;
                }
            })
            ->pluck('id')
        );
    }

    public function scopeWithTin($query, $tin)
    {
        return $query->whereIn('id', Vendor::where('kyb_status', UserKYBStatusEnum::SUCCESSFUL)->get()
            ->filter(function($user) use ($tin) {
                try {
                    return Crypt::decryptString($user->tin) === $tin;
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    // Log the error for debugging
                    Log::warning("Failed to decrypt TIN for user {$user->id}: " . $e->getMessage());
                    return false;
                }
            })
            ->pluck('id')
        );
    }

    public function isOpen ():bool
    {
        $currentTime = now()->format('H:i');
        $openingTime = $this->opening_time?->format('H:i');
        $closingTime = $this->closing_time?->format('H:i');

        return $currentTime >= $openingTime && $currentTime <= $closingTime;
    }
}
