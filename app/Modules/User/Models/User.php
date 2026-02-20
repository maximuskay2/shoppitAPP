<?php

namespace App\Modules\User\Models;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\Review;
use App\Modules\Transaction\Models\PaymentMethod;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\Wallet;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\Transaction\Models\DriverPayout;
use App\Modules\User\Enums\UserKYCStatusEnum;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\DeviceToken;
use App\Modules\User\Models\Driver;
use App\Modules\User\Models\DriverLocation;
use App\Modules\User\Models\DriverPaymentDetail;
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
use Database\Factories\UserFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, UUID, SoftDeletes;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
        protected $guarded = [];

        /**
         * The attributes that should be cast to native types.
         *
         * @var array
         */
        protected $casts = [
            'failed_login_attempts' => 'integer',
            'lockout_until' => 'datetime',
        ];

        /**
         * The attributes that are mass assignable.
         *
         * @var list<string>
         */
        protected $fillable = [
            'name',
            'username',
            'email',
            'phone',
            'password',
            'status',
            'email_verified_at',
            'avatar',
            'country',
            'state',
            'city',
            'address',
            'address_2',
            'kyc_status',
            'push_in_app_notifications',
            'failed_login_attempts',
            'lockout_until',
            'last_logged_in_at',
            'last_logged_in_device',
            'referred_by_user_id',
        ];

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
        'authorization_code',
        'email_token',
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
            'status' => UserStatusEnum::class,
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

    public function isActive(): bool
    {
        return $this->status === UserStatusEnum::ACTIVE;
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function searches(): HasMany
    {
        return $this->hasMany(SearchHistory::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }

    public function driverLocations(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function driverEarnings(): HasMany
    {
        return $this->hasMany(DriverEarning::class, 'driver_id');
    }

    public function driverPayouts(): HasMany
    {
        return $this->hasMany(DriverPayout::class, 'driver_id');
    }

    public function driverDocuments(): HasMany
    {
        return $this->hasMany(DriverDocument::class, 'driver_id');
    }

    public function driverSupportTickets(): HasMany
    {
        return $this->hasMany(DriverSupportTicket::class, 'driver_id');
    }

    public function driverPaymentDetails(): HasMany
    {
        return $this->hasMany(DriverPaymentDetail::class, 'driver_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
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
