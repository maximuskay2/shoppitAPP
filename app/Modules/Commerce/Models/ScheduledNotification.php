<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledNotification extends Model
{
    use UUID;

    protected $guarded = [];

    protected $casts = [
        'target_user_ids' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $hidden = ['id', 'template_id'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function markAsSent(int $deliveredCount = 0, int $failedCount = 0): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'delivered_count' => $deliveredCount,
            'failed_count' => $failedCount,
        ]);
    }

    public function cancel(): void
    {
        if ($this->isPending()) {
            $this->update(['status' => 'cancelled']);
        }
    }
}
