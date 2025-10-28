<?php

namespace App\Modules\User\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDetails extends Model
{
    use HasFactory, UUID;

    protected $table = 'payment_details'; 

    protected $guarded = [];

    protected $casts = [
        'subaccount_codes' => 'array',
        'recipient_codes' => 'array',
    ];
}
