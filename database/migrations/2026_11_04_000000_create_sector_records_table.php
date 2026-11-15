<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each player's best time per sector of a map; the sector record is the lowest of them.
        Schema::create('sector_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->unsignedBigInteger('player_id');
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->unsignedSmallInteger('sector');
            $table->unsignedInteger('time');
            $table->unique(['map_id', 'sector', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_records');
    }
};
