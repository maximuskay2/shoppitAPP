<?php

namespace App\Modules\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Modules\Blockchain\Models\Wallet;
use App\Modules\Blockchain\Models\UserBalance;
use App\Modules\Commerce\Models\Order;
use App\Modules\User\Enums\UserKYCStatusEnum;
use App\Modules\User\Models\DeviceToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Facades\Laravolt\Avatar\Avatar;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use App\Modules\User\Enums\KYCLevelEnum;
// use App\Modules\User\Enums\KYCStatusEnum;
use App\Modules\User\Models\KycVerification;
use App\Modules\User\Models\KycDocument;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, UUID, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'last_logged_in_at',
        'last_logged_in_device',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'kyc_status' => UserKYCStatusEnum::class,
            'push_in_app_notifications' => 'boolean',
            'last_logged_in_at' => 'datetime',
        ];
    }

    /**
     * Creates an avatar using user's email
     * @return mixed
     */
    public function createAvatar()
    {
        return Avatar::create($this->email)->toBase64();
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    // KYC Helper Methods
    public function isKycVerified(): bool
    {
        return $this->kyc_status === UserKYCStatusEnum::SUCCESSFUL;
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
