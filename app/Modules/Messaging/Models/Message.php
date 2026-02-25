<?php

namespace App\Modules\Messaging\Models;

use App\Modules\User\Models\Admin;
use App\Modules\User\Models\User;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use UUID;

    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getSenderAttribute(): Admin|User|null
    {
        return $this->sender_type === 'admin'
            ? Admin::find($this->sender_id)
            : User::find($this->sender_id);
    }
}
