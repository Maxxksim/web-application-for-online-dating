<?php

namespace App\Models;

use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy([UserObserver::class])]
#[Fillable(['email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, Billable;

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'notifications.' . $this->id;
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function swipedBy(): HasMany
    {
        return $this->hasMany(Swipe::class, 'swiper_id');
    }

    public function receivedSwipes(): HasMany
    {
        return $this->hasMany(Swipe::class, 'swiped_id');
    }

    public function mutualLikes(): HasMany
    {
        return $this->hasMany(MutualLike::class);
    }

    public function searchFilter(): HasOne
    {
        return $this->hasOne(SearchFilters::class);
    }

    public function geolocation(): HasOne
    {
        return $this->hasOne(Geolocation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function chats(): belongsToMany
    {
        return $this->belongsToMany(Chat::class);
    }

}
