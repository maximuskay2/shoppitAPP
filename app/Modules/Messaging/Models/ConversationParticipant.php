<?php

namespace App\Modules\Messaging\Models;

use App\Modules\User\Models\Admin;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $guarded = [];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getParticipantAttribute(): Admin|User|null
    {
        return $this->participant_type === 'admin'
            ? Admin::find($this->participant_id)
            : User::find($this->participant_id);
    }
}
