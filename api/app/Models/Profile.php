<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable(['user_id', 'name', 'date_of_birth', 'gender', 'country', 'city', 'description', 'relevance_score', 'manually_disabled', 'completion_percentage', 'is_enabled', 'relevance_score_updated_on', 'dating_purpose', 'height', 'weight', 'body_type', 'eye_color', 'hair_color', 'smoking', 'drinking', 'children', 'zodiac_sign', 'exercise'])]
class Profile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class);
    }

    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    protected function casts(): array
    {
        return [
            'relevance_score_updated_on' => 'date',
            'is_enabled' => 'boolean'
        ];
    }

    public function interests(): HasMany
    {
        return $this->hasMany(Interest::class);
    }
}
