<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remito extends Model
{
    protected $table = 'remitos';

    protected $fillable = [
        'n_remito',
        'cliente',
        'estado',
    ];

    public function modelos()
    {
        return $this->belongsToMany(Modelo::class, 'remitos_modelos')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
}
