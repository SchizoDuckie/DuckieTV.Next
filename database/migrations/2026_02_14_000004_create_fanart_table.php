<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fanart', function (Blueprint $table) {
            $table->id();
            $table->integer('tvdb_id')->index();
            $table->string('poster', 255)->nullable();
            $table->text('json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fanart');
    }
};
