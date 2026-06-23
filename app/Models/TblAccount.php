<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-customer running balance. balance > 0 means the customer owes money (due).
 */
class TblAccount extends Model
{
    protected $table = 'tblaccounts';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Radcheck::class, 'id');
    }
}
