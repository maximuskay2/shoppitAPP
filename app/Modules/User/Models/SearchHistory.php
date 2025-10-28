<?php

namespace App\Modules\User\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory, UUID;

    protected $table = 'search_histories'; 

    protected $guarded = [];
}
