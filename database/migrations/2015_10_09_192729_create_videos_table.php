<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->length(10)->unsigned();
            $table->integer('prompt_id')->length(10)->unsigned();
            $table->string('video_url');
            $table->string('thumb_url');
            $table->integer('duration')->default(0);
            $table->text('metadata')->nullable();
            $table->timestamps();

            //foreign key constraints
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('prompt_id')->references('id')->on('prompts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('videos');
    }
}
