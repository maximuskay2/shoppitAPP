<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledNotification extends Model
{
    use HasFactory;

    protected $table = 'scheduled_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'data',
        'type',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
    ];
}
