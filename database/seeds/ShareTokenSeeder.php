<?php

use Illuminate\Database\Seeder;

class ShareTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    		$users = App\User::all();
    		foreach ($users as $user) {
    			$user->share_token = str_random(10);
    			$user->save();
    		}
    }
}
