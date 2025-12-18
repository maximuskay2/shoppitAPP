<?php

namespace App\Modules\User\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    use HasFactory, UUID;

    protected $table = 'favourites';

    protected $guarded = [];

    public function favouritable()
    {
        return $this->morphTo();
    }
}
