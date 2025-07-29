<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stock';

    protected $fillable = [
        'modelo',
        'nombreModelo',
        'prearmado',
        'inyectado',
        'motores',
        'embalados',
    ];
}
