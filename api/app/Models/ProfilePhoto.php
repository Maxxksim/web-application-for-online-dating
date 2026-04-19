<?php

namespace App\Models;

use App\Policies\ProfilePhotoPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(ProfilePhotoPolicy::class)]
#[Fillable(['profile_id', 'path', 'is_approved'])]
class ProfilePhoto extends Model
{
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
