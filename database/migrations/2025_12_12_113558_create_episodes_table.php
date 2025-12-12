<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anime_id')->constrained('anime')->onDelete('cascade');
            $table->integer('episode_number');
            $table->string('title')->nullable();
            $table->string('player_url', 1024)->nullable();
            $table->string('external_id', 64)->nullable();
            $table->string('external_source', 32)->nullable();
            $table->timestampTz('aired_at')->nullable();
            $table->integer('duration')->nullable();
            $table->string('thumbnail_url', 1024)->nullable();
            $table->timestamps();

            $table->unique(['anime_id', 'episode_number']);
            $table->unique(['external_source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
