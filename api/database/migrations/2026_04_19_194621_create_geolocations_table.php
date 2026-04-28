<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('geolocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->geometry('geo_point', 'point', 4326)->nullable();
            $table->spatialIndex('geo_point');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geolocations');
    }
};
