<?php

use Illuminate\Database\Seeder;

class FuneralHomesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$texts = array(
    		'Sharp Funerals',
    		'O\'Connor',
    		'Bradshaw',
    		'Chicago Jewish'
    	);
    	foreach ($texts as $t){
    		DB::table('funeral_homes')->insert([
              'name' => $t
          	]);	
    	}
    }
}