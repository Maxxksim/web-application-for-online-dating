<?php

namespace App\Services;

use App\Events\LikeRetracted;
use App\Events\LikeSent;
use App\Events\MatchCreated;
use App\Models\MutualLike;
use App\Models\Swipe;
use App\Models\User;
use App\Notifications\LikeNotification;
use Illuminate\Support\Facades\DB;

class SwipeService
{
    public function swipe(int $swiperId, int $swipedId, bool $isLiked): void
    {
        $swipe = Swipe::create([
            'swiper_id' => $swiperId,
            'swiped_id' => $swipedId,
            'is_liked' => $isLiked
        ]);

        if (!$isLiked) {
            return;
        }

        if ($this->isMatch($swiperId, $swipedId)) {
            $this->handleMatch($swiperId, $swipedId);
            return;
        }

        LikeSent::dispatch($swipe);
    }

    public function isSwiped(int $swiperId, int $swipedId): bool
    {
        return Swipe::where('swiper_id', $swiperId)->where('swiped_id', $swipedId)->exists();
    }

    private function isMatch(int $swiperId, int $swipedId): bool
    {
        return Swipe::where('swiper_id', $swipedId)
            ->where('swiped_id', $swiperId)
            ->where('is_liked', true)
            ->exists();
    }

    private function handleMatch(int $swiperId, int $swipedId): void
    {
        $match = MutualLike::create([
            'first_user_id' => $swiperId,
            'second_user_id' => $swipedId,
        ]);

        $this->markLikeNotificationAsRead($swipedId, $swiperId);
        MatchCreated::dispatch($match);
    }

    public function rollbackSwipe(int $swiperId, int $swipedId): void
    {
        $swipe = Swipe::where('swiper_id', $swiperId)
            ->where('swiped_id', $swipedId)
            ->first();

        DB::transaction(function () use ($swipe, $swiperId, $swipedId) {
            $swipe->delete();

            if (!$swipe->is_liked) {
                return;
            }

            $this->retractLikeNotification($swiperId, $swipedId);
            LikeRetracted::dispatch($swipe);
        });
    }


    private function retractLikeNotification(int $fromUserId, int $toUserId): void
    {
        $this->likeNotificationQuery($fromUserId, $toUserId)->delete();
    }

    private function markLikeNotificationAsRead(int $fromUserId, int $toUserId): void
    {
        $this->likeNotificationQuery($fromUserId, $toUserId)
            ->update(['read_at' => now()]);
    }

    private function likeNotificationQuery(int $fromUserId, int $toUserId)
    {
        return User::find($toUserId)
            ->notifications()
            ->where('type', LikeNotification::class)
            ->whereRaw("(data::jsonb->>'user_id')::integer = ?", [$fromUserId]);
    }
}
