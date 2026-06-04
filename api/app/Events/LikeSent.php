<?php

namespace App\Events;

use App\Models\Swipe;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LikeSent implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Swipe $swipe,
    )
    {
    }
}
