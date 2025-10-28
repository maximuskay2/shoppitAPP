<?php

namespace App\Modules\User\Models;

use App\Modules\P2PTrading\Models\P2PTransaction;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory, UUID;

    protected $table = 'notifications';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'p2_p_transaction_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function p2pTransaction()
    {
        return $this->belongsTo(P2PTransaction::class, 'p2_p_transaction_id');
    }

    /* Scopes */
    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
