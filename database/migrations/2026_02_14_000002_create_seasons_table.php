<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->constrained('series')->cascadeOnDelete();
            $table->string('poster', 255)->nullable();
            $table->text('overview')->nullable();
            $table->integer('seasonnumber');
            $table->integer('ratings')->nullable();
            $table->integer('ratingcount')->nullable();
            $table->boolean('watched')->default(false);
            $table->integer('notWatchedCount')->default(0);
            $table->integer('trakt_id')->nullable();
            $table->integer('tmdb_id')->nullable();
            $table->timestamps();

            $table->unique(['serie_id', 'seasonnumber', 'trakt_id']);
            $table->index('serie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
