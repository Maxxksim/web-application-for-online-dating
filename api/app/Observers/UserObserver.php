<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class UserObserver implements ShouldHandleEventsAfterCommit
{
    public function created(User $user): void
    {
        $user->profile()->create();
        $user->searchFilter()->create();
        $user->geolocation()->create();
    }
}
