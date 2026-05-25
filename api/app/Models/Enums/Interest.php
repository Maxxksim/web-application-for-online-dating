<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum Interest: string
{
    use EnumToArrayTrait;
    case MUSIC = 'music';
    case GUITAR = 'guitar';
    case PIANO = 'piano';
    case SINGING = 'singing';
    case DJ = 'dj';
    case CONCERTS = 'concerts';
    case MOVIES = 'movies';
    case SERIES = 'series';
    case ANIME = 'anime';
    case DOCUMENTARIES = 'documentaries';
    case READING = 'reading';
    case SELF_DEVELOPMENT = 'self_development';
    case PSYCHOLOGY = 'psychology';
    case PHILOSOPHY = 'philosophy';
    case HISTORY = 'history';
    case GYM = 'gym';
    case YOGA = 'yoga';
    case RUNNING = 'running';
    case CYCLING = 'cycling';
    case SWIMMING = 'swimming';
    case MARTIAL_ARTS = 'martial_arts';
    case FOOTBALL = 'football';
    case BASKETBALL = 'basketball';
    case VOLLEYBALL = 'volleyball';
    case TENNIS = 'tennis';
    case SKIING = 'skiing';
    case SNOWBOARDING = 'snowboarding';
    case CLIMBING = 'climbing';
    case DANCING = 'dancing';
    case BOXING = 'boxing';
    case COOKING = 'cooking';
    case BAKING = 'baking';
    case COFFEE = 'coffee';
    case WINE = 'wine';
    case RESTAURANTS = 'restaurants';
    case VEGETARIANISM = 'vegetarianism';
    case TRAVELING = 'traveling';
    case BACKPACKING = 'backpacking';
    case CAMPING = 'camping';
    case ROAD_TRIPS = 'road_trips';
    case VIDEO_GAMES = 'video_games';
    case BOARD_GAMES = 'board_games';
    case POKER = 'poker';
    case CHESS = 'chess';
    case DRAWING = 'drawing';
    case PAINTING = 'painting';
    case PHOTOGRAPHY = 'photography';
    case DESIGN = 'design';
    case WRITING = 'writing';
    case BLOGGING = 'blogging';
    case CRAFTS = 'crafts';
    case DOGS = 'dogs';
    case CATS = 'cats';
    case ANIMALS = 'animals';
    case HIKING = 'hiking';
    case FISHING = 'fishing';
    case GARDENING = 'gardening';
    case SURFING = 'surfing';
    case PROGRAMMING = 'programming';
    case CRYPTO = 'crypto';
    case STARTUPS = 'startups';
    case AI = 'ai';
    case MEDITATION = 'meditation';
    case SPIRITUALITY = 'spirituality';
    case ASTROLOGY = 'astrology';
    case THEATER = 'theater';
    case COMEDY = 'comedy';
    case KARAOKE = 'karaoke';
    case NIGHTLIFE = 'nightlife';
}
