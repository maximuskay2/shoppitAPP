<?php

namespace App\Modules\User\Models;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Cart;
use App\Modules\User\Enums\UserKYCStatusEnum;
use App\Modules\User\Models\DeviceToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Facades\Laravolt\Avatar\Avatar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\MessageTarget;

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
        'customer_code',
        'authorization_code'
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

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }   

    /**
     * Route notifications for the mail channel.
     *
     * @return  array<string, string>|string
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        // Return email address and name...
        return [$this->email => $this->name ?? null];
    }


    /**
     * Route notifications for the fcm channel.
     *
     * @return  array<string, string>|string
     */
    public function routeNotificationForFCM($notification)
    {
        return $this->deviceTokens()->whereStatus('ACTIVE')->pluck('token')->toArray();
    }

    /**
     * Optional method to determine which message target to use
     * We will use TOKEN type when not specified
     *
     * @see MessageTarget::TYPES
     */
    public function routeNotificationForFCMTargetType($notification)
    {
        return MessageTarget::TOKEN;
    }

    /**
     * Optional method to determine which Firebase project to use
     * We will use default project when not specified
     */
    public function routeNotificationForFCMProject($notification)
    {
        return config('firebase.default');
    }
}
