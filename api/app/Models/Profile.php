<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable(['user_id', 'name', 'date_of_birth', 'gender', 'country', 'city', 'description', 'relevance_score', 'completion_percentage', 'is_enabled', 'relevance_score_updated_on', 'dating_purpose'])]
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
        ];
    }
}
