<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_get_notification_after_mutual_like()
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

        $this->assertCount(1, $firstUserNotifications);
        $this->assertCount(1, $secondUserNotifications);

    }
}
