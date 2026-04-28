<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['first_profile_id', 'second_profile_id', 'is_active'])]
class MutualLike extends Model
{
    public function firstProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'first_profile_id');
    }

    public function secondProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'second_profile_id');
    }
}
