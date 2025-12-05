<?php

namespace App\Modules\Commerce\Models;

use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    use HasFactory, UUID;

    protected $table = 'waitlists';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}