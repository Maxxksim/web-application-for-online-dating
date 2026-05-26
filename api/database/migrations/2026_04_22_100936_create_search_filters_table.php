<?php

use App\Models\Enums\BodyType;
use App\Models\Enums\ChildrenStatus;
use App\Models\Enums\DatingPurpose;
use App\Models\Enums\EyeColor;
use App\Models\Enums\Habit;
use App\Models\Enums\HairColor;
use App\Models\Enums\ZodiacSign;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->integer('min_age')->default(16);
            $table->integer('max_age')->default(50);
            $table->enum('gender', ['man', 'woman', 'both'])->nullable();
            $table->integer('distance')->default(10);
            $table->json('interests')->nullable();
            $table->enum('dating_purpose', DatingPurpose::toArray())->nullable();
            $table->float('min_height')->nullable();
            $table->float('max_height')->nullable();
            $table->float('min_weight')->nullable();
            $table->float('max_weight')->nullable();
            $table->enum('body_type', BodyType::toArray())->nullable();
            $table->enum('eye_color', EyeColor::toArray())->nullable();
            $table->enum('hair_color', HairColor::toArray())->nullable();
            $table->enum('smoking', Habit::toArray())->nullable();
            $table->enum('drinking', Habit::toArray())->nullable();
            $table->enum('children', ChildrenStatus::toArray())->nullable();
            $table->enum('zodiac_sign', ZodiacSign::toArray())->nullable();
            $table->enum('exercise', Habit::toArray())->nullable();
            $table->boolean('use_advanced_filters')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_filters');
    }
};
