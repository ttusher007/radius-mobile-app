<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TblPayment extends Model
{
    protected $table = 'tblpayment';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'cid' => 'integer',
        'amt' => 'integer',
        'ledger_id' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Radcheck::class, 'cid');
    }
}
