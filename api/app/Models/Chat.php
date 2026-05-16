<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['last_message_at'])]
class Chat extends Model
{
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function firstUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_user_id');
    }

    public function secondUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_user_id');
    }

    public function interlocutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interlocutor_id');
    }

}
