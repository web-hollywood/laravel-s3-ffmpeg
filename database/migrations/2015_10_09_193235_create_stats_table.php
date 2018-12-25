<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->length(10)->unsigned();
            $table->integer('video_id')->length(10)->unsigned();
            $table->string('ip', 50);
            $table->integer('duration');
            $table->timestamps();

            //foreign key constraints
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('video_id')->references('id')->on('videos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stats');
    }
}
