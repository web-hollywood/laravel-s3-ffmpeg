<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */

    use SoftDeletes;

    protected $table = 'access_codes';
    protected $dates = ['deleted_at'];

    const LENGTH = 7;
}
