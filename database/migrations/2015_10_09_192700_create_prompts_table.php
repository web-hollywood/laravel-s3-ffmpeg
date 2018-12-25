<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->length(10)->unsigned()->nullable();
            $table->integer('category_id')->length(10)->unsigned()->nullable();
            $table->text('text');
            $table->timestamps();

            //foreign key constraints
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('prompts');
    }
}
