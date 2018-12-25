<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'prompts';

    /**
     * The attributes included in the model's JSON form.
     *
     * @var array
     */
    protected $visible = ['id', 'text', 'category_id', 'user_id'];

    /**
     * Get the prompt creator
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    /**
     * Get the video
     */
    public function videos()
    {
        return $this->hasMany('App\Video');
    }
}
