<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tmdb_fanart', function (Blueprint $table) {
            $table->id();
            $table->integer('entity_type');
            $table->integer('tmdb_id');
            $table->text('poster')->nullable();
            $table->text('fanart')->nullable();
            $table->text('screenshot')->nullable();
            $table->date('added')->nullable();
            $table->timestamps();

            $table->index('entity_type');
            $table->index('tmdb_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmdb_fanart');
    }
};
