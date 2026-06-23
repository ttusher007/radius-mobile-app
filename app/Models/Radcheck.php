<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A radius customer (radcheck).
 *
 * Status is derived: tmpdel = 1 -> Closed, enableuser = 1 -> Enabled, else Disabled.
 */
class Radcheck extends Model
{
    protected $table = 'radcheck';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'enableuser' => 'integer',
        'tmpdel' => 'integer',
        'expiredate' => 'date',
    ];

    public function account(): HasOne
    {
        return $this->hasOne(TblAccount::class, 'id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'packageid');
    }

    public function pop(): BelongsTo
    {
        return $this->belongsTo(Pop::class, 'allowpopid');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'resellerid');
    }

    /** Exclude soft-closed customers. */
    public function scopeNotClosed(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.tmpdel', 0);
    }

    public function getStatusLabelAttribute(): string
    {
        if ((int) $this->tmpdel === 1) {
            return 'Closed';
        }

        return (int) $this->enableuser === 1 ? 'Enabled' : 'Disabled';
    }
}
