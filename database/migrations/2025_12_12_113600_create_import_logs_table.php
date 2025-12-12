<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('import_type', 50);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->integer('total_processed')->default(0);
            $table->integer('total_created')->default(0);
            $table->integer('total_updated')->default(0);
            $table->integer('total_skipped')->default(0);
            $table->text('errors')->nullable();
            $table->string('status', 50)->default('running');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
