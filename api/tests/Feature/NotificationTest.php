<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    private array $expectedNotificationTypes = [
        'Match' => 'App\Notifications\MatchNotification',
        'Like' => 'App\Notifications\LikeNotification'
    ];

    public function test_get_notification_after_like_and_match(): void
    {
        $firstUser = $this->getUser();
        $secondUser = $this->getUser();

        $firstUserId = $firstUser['user']->id;
        $secondUserId = $secondUser['user']->id;

        $this->actingAs($firstUser['user'], 'sanctum')
            ->post("/api/swipes/{$secondUserId}", ['is_liked' => true]);

        $this->actingAs($secondUser['user'], 'sanctum')
            ->post("/api/swipes/{$firstUserId}", ['is_liked' => true]);

        $firstUserNotifications = $this->actingAs($firstUser['user'], 'sanctum')->get('/api/notifications')['notifications'];

        $secondUserNotifications = $this->actingAs($secondUser['user'], 'sanctum')->get('/api/notifications')['notifications'];

        $this->assertCount(2, $firstUserNotifications);
        $this->assertEquals($this->expectedNotificationTypes['Like'], $firstUserNotifications[0]['type']);
        $this->assertEquals($this->expectedNotificationTypes['Match'], $firstUserNotifications[1]['type']);

        $this->assertCount(2, $secondUserNotifications);
        $this->assertEquals($this->expectedNotificationTypes['Like'], $secondUserNotifications[0]['type']);
        $this->assertEquals($this->expectedNotificationTypes['Match'], $secondUserNotifications[1]['type']);
    }
}
