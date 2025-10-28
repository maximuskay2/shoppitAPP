<?php

namespace App\Modules\User\Listeners;

use App\Modules\User\Events\UserCreatedEvent;
use App\Modules\User\Notifications\WelcomeOnboardNotification;
use App\Modules\User\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeOnboardNotificationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected UserService $userService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreatedEvent $event): void
    {
        $user = $event->user;

        $user->notify(new WelcomeOnboardNotification());
    }
}
