<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class InvalidPhone extends Model
{

    public $incrementing = false;
    public $timestamps = false;
    public $fillable = [
        'id',
    ];

}