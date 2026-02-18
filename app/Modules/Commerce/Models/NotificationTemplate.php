<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    use UUID;

    protected $guarded = [];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['id'];

    public function scheduledNotifications(): HasMany
    {
        return $this->hasMany(ScheduledNotification::class, 'template_id');
    }

    /**
     * Replace variables in title and body with actual values
     */
    public function render(array $data = []): array
    {
        $title = $this->title;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $title = str_replace('{{' . $key . '}}', $value, $title);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        return [
            'title' => $title,
            'body' => $body,
        ];
    }
}
