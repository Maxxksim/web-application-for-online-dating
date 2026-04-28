<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('mutual_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('first_profile_id')->constrained('profiles', column: 'id')->cascadeOnDelete();
            $table->foreignId('second_profile_id')->constrained('profiles', 'id')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutual_likes');
    }
};
