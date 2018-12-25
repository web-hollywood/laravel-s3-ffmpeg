<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the videos associated with the user.
     */
    public function videos()
    {
        return $this->hasMany('App\Video');
    }

    /**
     * Get the prompts associated with the user.
     */
    public function prompts()
    {
        return $this->hasMany('App\Prompt');
    }

    /**
     * Get the funeral home associated with the user
     */
    public function funeral_home(){
        return $this->belongsTo('App\FuneralHome');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(){
        return $this->user_level >= 1;
    }

    /**
     * returns user's full name
     */
    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Post model
    public function videosCountLastDay()
    {
        return $this->hasOne('App\Video')
            ->selectRaw('user_id, count(*) as aggregate')
            ->groupBy('user_id');
    }

    public function getVideosCountLastDayAttribute()
    {
        // if relation is not loaded already, let's do it first
        if ( ! array_key_exists('videosCountLastDay', $this->relations))
            $this->load('videosCountLastDay');

        $related = $this->getRelation('videosCountLastDay');

        // then return the count directly
        return ($related) ? (int) $related->aggregate : 0;
    }

    /**
    * Sends out welcome email with verification code
    */
    public function sendWelcomeEmail() {
        \Mail::send('emails.welcome', ['confirmation_code' => $this->confirmation_code], function($message) {
            $message->to($this->email, $this->username)
                ->subject('Welcome to mylegacysuite.com');
        });
    }
}
