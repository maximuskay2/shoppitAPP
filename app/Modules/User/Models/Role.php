<?php

namespace App\Modules\User\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, UUID;

    protected $guarded = [];

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    // public static function getUserRoleId(): string
    // {
    //     return self::select('id')->whereName(RoleEnum::USER->value)->first()->id;
    // }
}
