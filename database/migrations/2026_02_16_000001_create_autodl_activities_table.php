<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('autodl_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->nullable()->constrained('series')->onDelete('set null');
            $table->foreignId('episode_id')->nullable()->constrained('episodes')->onDelete('set null');
            $table->text('search');
            $table->string('search_provider')->nullable();
            $table->string('search_extra')->nullable();
            $table->integer('status');
            $table->string('extra')->nullable();
            $table->string('serie_name');
            $table->string('episode_formatted');
            $table->bigInteger('timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autodl_activities');
    }
};
