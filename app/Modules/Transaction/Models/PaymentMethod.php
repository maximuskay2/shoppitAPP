<?php

namespace App\Modules\Transaction\Models;

use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, UUID;

    protected $table = 'payment_methods';
    protected $guarded = [];

    protected $hidden = [
        'authorization_code',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
