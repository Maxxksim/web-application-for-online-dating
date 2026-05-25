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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['man', 'woman'])->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('description')->nullable();
            $table->integer('relevance_score')->default(3);
            $table->boolean('is_enabled')->default(false);
            $table->integer('completion_percentage')->default(0);
            $table->date('relevance_score_updated_on')->nullable();
            $table->enum('dating_purpose', DatingPurpose::toArray())->nullable();
            $table->float('height')->nullable();
            $table->float('weight')->nullable();
            $table->enum('body_type', BodyType::toArray())->nullable();
            $table->enum('eye_color', EyeColor::toArray())->nullable();
            $table->enum('hair_color', HairColor::toArray())->nullable();
            $table->enum('smoking', Habit::toArray())->nullable();
            $table->enum('drinking', Habit::toArray())->nullable();
            $table->enum('children', ChildrenStatus::toArray())->nullable();
            $table->enum('zodiac_sign', ZodiacSign::toArray())->nullable();
            $table->enum('exercise', Habit::toArray())->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
