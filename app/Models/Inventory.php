<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable=[
        'silver_weight',
        'average_price'
    ];
}
