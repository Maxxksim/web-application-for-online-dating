<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['min_age', 'max_age', 'gender', 'distance'])]
class SearchFilters extends Model
{
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
