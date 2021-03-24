<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Socket extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chartConnection',function(Blueprint  $table){
            $table->id();
            $table->integer('roomId');
            $table->tinyInteger('source');
            $table->integer('fd');
            $table->tinyInteger('status');
            $table->timestamps();
        });
        Schema::create('chatMessage',function(Blueprint $table) {
            $table->id();
            $table->tinyInteger('type');
            $table->tinyInteger('source');
            $table->text('message');
            $table->tinyInteger('status');
            $table->integer('roomId');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chartConnection');
        Schema::dropIfExists('chatMessage');
    }
}
