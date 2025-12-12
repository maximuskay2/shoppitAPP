<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, UUID;
    
    protected $fillable = [
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

    public function providers(): HasMany
    {
        return $this->hasMany(ServiceProvider::class);
    }
}

