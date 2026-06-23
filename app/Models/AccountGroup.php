<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountGroup extends Model
{
    protected $table = 'account_groups';

    protected $primaryKey = 'Account_Group_Id';

    public $timestamps = false;

    protected $guarded = [];
}
