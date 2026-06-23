<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pop extends Model
{
    protected $table = 'uz_poplist';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Only active POPs (status = 1).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('uz_poplist.status', 1);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'allowresellerid');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pop_user', 'pop_id', 'user_id');
    }
}
