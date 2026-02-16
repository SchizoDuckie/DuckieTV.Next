<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->constrained('series')->cascadeOnDelete();
            $table->unsignedBigInteger('season_id')->nullable();
            $table->integer('tvdb_id')->nullable();
            $table->string('episodename', 255)->nullable();
            $table->integer('episodenumber')->nullable();
            $table->integer('seasonnumber')->nullable();
            $table->bigInteger('firstaired')->nullable();
            $table->string('firstaired_iso', 25)->nullable();
            $table->string('imdb_id', 20)->nullable();
            $table->string('language', 3)->nullable();
            $table->text('overview')->nullable();
            $table->integer('rating')->nullable();
            $table->integer('ratingcount')->nullable();
            $table->string('filename', 255)->nullable();
            $table->text('images')->nullable();
            $table->integer('watched')->default(0);
            $table->bigInteger('watchedAt')->nullable();
            $table->integer('downloaded')->default(0);
            $table->string('magnetHash', 40)->nullable();
            $table->integer('trakt_id')->unique()->nullable();
            $table->integer('leaked')->default(0);
            $table->integer('absolute')->nullable();
            $table->integer('tmdb_id')->nullable();
            $table->timestamps();

            $table->foreign('season_id')->references('id')->on('seasons')->nullOnDelete();
            $table->index('serie_id');
            $table->index('tvdb_id');
            $table->index('trakt_id');
            $table->index('season_id');
            $table->index(['serie_id', 'firstaired']);
            $table->index('watched');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
