<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{

		/**
     * The database table used by the model.
     *
     * @var string
     */
    use SoftDeletes;

    protected $table = 'videos';
    protected $dates = ['deleted_at'];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the prompt
     */
    public function prompt()
    {
        return $this->belongsTo('App\Prompt');
    }
}
