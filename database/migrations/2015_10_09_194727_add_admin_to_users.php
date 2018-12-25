<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdminToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->insert(
            array(
                'username' => 'admin',
                'email' => 'admin@legacysuite.com',
                'first_name' => 'Michael',
                'last_name' => 'Curry',
                'phone' => '',
                'address' => '',
                'city' => '',
                'state' => '',
                'zip' => '',
                'country' => '',
                'birthday' => '1980-01-01',
                'user_level' => 1,
                'status' => 1,
                'password' => Hash::make('password')
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')->delete(
            array('username' => 'admin')
        );
    }
}
