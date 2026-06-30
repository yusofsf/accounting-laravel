<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable=[
        'rubika_group_id',
        'title'
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(SilverTransaction::class);
    }
}
