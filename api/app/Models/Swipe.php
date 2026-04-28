<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['swiper_id', 'swiped_id', 'is_liked'])]
class Swipe extends Model
{
    public function profileSwiper(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'swiper_id');
    }

    public function profileSwiped(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'swiped_id');
    }
}
