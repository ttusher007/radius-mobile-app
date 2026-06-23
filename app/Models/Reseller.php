<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A "Manager" in the billing UI maps to a reseller row (uz_resellers).
 */
class Reseller extends Model
{
    protected $table = 'uz_resellers';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Only active resellers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('uz_resellers.active', 1);
    }

    public function pops(): HasMany
    {
        return $this->hasMany(Pop::class, 'allowresellerid');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reseller_user', 'reseller_id', 'user_id');
    }
}
