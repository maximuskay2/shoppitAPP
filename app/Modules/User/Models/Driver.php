<?php

namespace App\Modules\User\Models;

use App\Traits\UUID;
use App\Modules\User\Models\DriverVehicle;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class, 'user_id', 'user_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(DriverVehicle::class);
    }

    protected static function newFactory(): DriverFactory
    {
        return DriverFactory::new();
    }
}
