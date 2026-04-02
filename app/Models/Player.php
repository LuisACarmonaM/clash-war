<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'name', 'week_1', 'week_2', 'week_3', 'week_4', 'week_5', 'week_6'
    ];
}
