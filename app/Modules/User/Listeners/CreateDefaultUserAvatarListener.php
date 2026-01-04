<?php

namespace App\Modules\User\Listeners;

use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Events\UserCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateDefaultUserAvatarListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreatedEvent $event): void
    {
        $user = $event->user;

        // Generate avatar bytes (PNG). If your method returns a file path, skip the temp file step.
        $bytes = $user->createAvatar();

        $tmp = tempnam(sys_get_temp_dir(), 'espays_ava_') . '.png';
        file_put_contents($tmp, $bytes);

        // Cloudinary PHP SDK v2: use uploadApi()->upload(...)
        $result = cloudinary()->uploadApi()->upload($tmp, [
            'folder' => 'espays/avatars',
            'overwrite' => true,
            'resource_type' => 'image',
        ]);

        @unlink($tmp);

        // Store the secure URL (adjust column name if needed)
        $user->avatar = $result['secure_url'] ?? $user->avatar;
        $user->status = UserStatusEnum::ACTIVE;
        $user->save();
    }
}
