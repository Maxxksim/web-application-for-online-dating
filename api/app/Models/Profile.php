<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'date_of_birth', 'gender', 'country', 'city', 'description', 'relevance_score', 'completion_percentage'])]
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

    public function updateCompletionPercentage(): void
    {
        $countPhotos = $this->photos()->count();
        $countFilled = collect($this->only(['name', 'date_of_birthday', 'gender', 'description']))->filter(fn($value) => $value !== null)->count();

        $this->update([
            'completion_percentage' => (int)(($countPhotos + $countFilled) / 7 * 100)
        ]);
    }
}
