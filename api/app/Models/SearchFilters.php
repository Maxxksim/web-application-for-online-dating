<?php

namespace App\Models;

use App\Models\Enums\BodyType;
use App\Models\Enums\ChildrenStatus;
use App\Models\Enums\DatingPurpose;
use App\Models\Enums\EyeColor;
use App\Models\Enums\Habit;
use App\Models\Enums\HairColor;
use App\Models\Enums\ZodiacSign;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['min_age', 'max_age', 'gender', 'distance', 'interests', 'dating_purpose', 'body_type', 'eye_color', 'hair_color', 'smoking', 'drinking', 'children', 'zodiac_sign', 'exercise', 'min_height', 'max_height', 'min_weight', 'max_weight'])]
class SearchFilters extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'interests' => AsCollection::class,
            'dating_purpose' => DatingPurpose::class,
            'body_type' => BodyType::class,
            'eye_color' => EyeColor::class,
            'hair_color' => HairColor::class,
            'smoking' => Habit::class,
            'drinking' => Habit::class,
            'children' => ChildrenStatus::class,
            'zodiac_sign' => ZodiacSign::class,
            'exercise' => Habit::class,
        ];
    }
}
