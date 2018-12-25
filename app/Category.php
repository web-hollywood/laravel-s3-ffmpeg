<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
  	/**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'categories';
    
    /**
     * The attributes included in the model's JSON form.
     *
     * @var array
     */
    protected $visible = ['id', 'text'];
}
