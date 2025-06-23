<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->integer('external_id')->unique()->nullable();
            $table->string('name', 100);
            $table->string('image', 500)->nullable();
            $table->timestamps();
            
            $table->index('external_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
