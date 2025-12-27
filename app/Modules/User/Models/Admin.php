<?php

namespace App\Modules\User\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, UUID, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'role_id',
        'password',
        'remember_token',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_super_admin' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['last_name', 'first_name'];

     /**
     * Return's the user's last name
     * @return string|null
     */
    public function getLastNameAttribute()
    {
        $parts = explode(' ', $this->name);

        if (count($parts) > 1) {
            return end($parts);
        }

        return null;
    }

    /**
     * Returns the user's first name
     * @return string|null
     */
    public function getFirstNameAttribute()
    {
        return explode(' ', $this->name)[0] ?? null;
    }

    public function role(): HasOne
    {
        return $this->hasOne(Role::class);
    }
}