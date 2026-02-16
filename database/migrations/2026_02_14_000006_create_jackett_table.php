<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jackett', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->nullable();
            $table->string('torznab', 200)->nullable();
            $table->integer('enabled')->default(0);
            $table->integer('torznabEnabled')->default(0);
            $table->string('apiKey', 40)->nullable();
            $table->text('json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jackett');
    }
};
