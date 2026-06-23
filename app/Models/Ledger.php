<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $table = 'ledgers';

    protected $primaryKey = 'Ledger_Id';

    public $timestamps = false;

    protected $guarded = [];
}
