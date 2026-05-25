<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['profile_id', 'interest'])]
class Interest extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
