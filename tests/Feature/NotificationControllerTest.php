<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\NotificationController;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_notification_with_fcm_retry_logic(): void
    {
        Notification::fake();
        $user = User::factory()->create(['push_in_app_notifications' => true]);
        $payload = [
            'user_id' => $user->id,
            'title' => 'Test FCM Retry',
            'body' => 'This is a test notification with retry.',
            'data' => ['foo' => 'bar'],
            'type' => 'test',
        ];
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/notifications/unified/send', $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        Notification::assertSentTimes(\App\Modules\User\Notifications\WelcomeOnboardNotification::class, 1);
    }
}
