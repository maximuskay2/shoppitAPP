<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceProvider extends Model
{
    use HasFactory, UUID;
    
    protected $fillable = [
        'service_id',
        'name',
        'status',
        'description',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // public function attributes()
    // {
    //     return $this->hasMany(ServiceProviderAttribute::class);
    // }

    // public function getActiveAttributes()
    // {
    //     return $this->attributes()->where('status', true)->get();
    // }
}

