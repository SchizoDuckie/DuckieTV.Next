<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250)->nullable();
            $table->string('banner', 1024)->nullable();
            $table->text('overview')->nullable();
            $table->integer('tvdb_id')->nullable();
            $table->string('imdb_id', 20)->nullable();
            $table->integer('tvrage_id')->nullable();
            $table->string('actors', 1024)->nullable();
            $table->string('airs_dayofweek', 10)->nullable();
            $table->string('airs_time', 15)->nullable();
            $table->string('timezone', 30)->nullable();
            $table->string('contentrating', 20)->nullable();
            $table->date('firstaired')->nullable();
            $table->string('genre', 50)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('language', 50)->nullable();
            $table->string('network', 50)->nullable();
            $table->integer('rating')->nullable();
            $table->integer('ratingcount')->nullable();
            $table->integer('runtime')->nullable();
            $table->string('status', 50)->nullable();
            $table->date('added')->nullable();
            $table->string('addedby', 50)->nullable();
            $table->string('fanart', 150)->nullable();
            $table->string('poster', 150)->nullable();
            $table->bigInteger('lastupdated')->nullable();
            $table->bigInteger('lastfetched')->nullable();
            $table->bigInteger('nextupdate')->nullable();
            $table->boolean('displaycalendar')->default(true);
            $table->boolean('autoDownload')->default(true);
            $table->string('customSearchString', 150)->nullable();
            $table->boolean('watched')->default(false);
            $table->integer('notWatchedCount')->default(0);
            $table->boolean('ignoreGlobalQuality')->default(false);
            $table->boolean('ignoreGlobalIncludes')->default(false);
            $table->boolean('ignoreGlobalExcludes')->default(false);
            $table->string('searchProvider', 20)->nullable();
            $table->boolean('ignoreHideSpecials')->default(false);
            $table->integer('customSearchSizeMin')->nullable();
            $table->integer('customSearchSizeMax')->nullable();
            $table->integer('trakt_id')->unique()->nullable();
            $table->text('dlPath')->nullable();
            $table->integer('customDelay')->nullable();
            $table->string('alias', 250)->nullable();
            $table->string('customFormat', 20)->nullable();
            $table->integer('tmdb_id')->nullable();
            $table->string('customIncludes', 150)->nullable();
            $table->string('customExcludes', 150)->nullable();
            $table->integer('customSeeders')->nullable();
            $table->timestamps();

            $table->index('trakt_id');
            $table->index('fanart');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
