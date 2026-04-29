<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['swiper_id', 'swiped_id', 'is_liked'])]
class Swipe extends Model
{
    public function swiper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'swiper_id');
    }

    public function swiped(): BelongsTo
    {
        return $this->belongsTo(User::class, 'swiped_id');
    }
}
