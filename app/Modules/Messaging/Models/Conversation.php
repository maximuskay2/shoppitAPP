<?php

namespace App\Modules\Messaging\Models;

use App\Modules\Commerce\Models\Order;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use UUID;

    protected $guarded = [];

    public const TYPE_ADMIN_DRIVER = 'admin_driver';
    public const TYPE_ADMIN_CUSTOMER = 'admin_customer';
    public const TYPE_ADMIN_VENDOR = 'admin_vendor';
    public const TYPE_DRIVER_CUSTOMER = 'driver_customer';
    public const TYPE_DRIVER_VENDOR = 'driver_vendor';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }
}
