<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anime', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('poster_url', 1024)->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('status', 32);
            $table->string('type', 32);
            $table->integer('number_of_episodes')->nullable();
            $table->string('external_id', 64)->nullable();
            $table->string('external_source', 32)->nullable();
            $table->date('aired_from')->nullable();
            $table->date('aired_to')->nullable();
            $table->boolean('nsfw_flag')->default(false);
            $table->integer('popularity')->nullable();
            $table->integer('favorites')->nullable();
            $table->integer('score_count')->nullable();
            $table->timestamps();

            $table->unique(['external_source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anime');
    }
};
