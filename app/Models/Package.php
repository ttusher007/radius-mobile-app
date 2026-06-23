<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'uz_package';

    public $timestamps = false;

    protected $guarded = ['id'];
}
