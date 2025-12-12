<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('translation_type', 50)->default('voice')->after('translator');
            $table->integer('priority')->default(50)->after('source');
            
            $table->index(['anime_id', 'episode_number', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropIndex(['anime_id', 'episode_number', 'source']);
            $table->dropColumn(['translation_type', 'priority']);
        });
    }
};
