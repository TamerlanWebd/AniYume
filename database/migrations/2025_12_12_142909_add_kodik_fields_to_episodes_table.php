<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('external_episode_id', 255)->nullable()->after('external_source');
            $table->integer('season_number')->nullable()->after('episode_number');
            $table->string('poster_url', 1024)->nullable()->after('thumbnail_url');
            $table->text('player_iframe')->nullable()->after('player_url');
            $table->string('translator', 255)->nullable()->after('player_iframe');
            $table->string('quality', 50)->nullable()->after('translator');
            $table->string('source', 50)->default('kodik')->after('quality');
            $table->date('release_date')->nullable()->after('aired_at');
            
            $table->unique('external_episode_id');
            $table->index('season_number');
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropUnique(['external_episode_id']);
            $table->dropIndex(['season_number']);
            
            $table->dropColumn([
                'external_episode_id',
                'season_number',
                'poster_url',
                'player_iframe',
                'translator',
                'quality',
                'source',
                'release_date',
            ]);
        });
    }
};
