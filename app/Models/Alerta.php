<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $fillable = [
        'fecha_alerta',
        'user_id',
        'serie',
        'modelo_id',
        'motivo',
        'solucionado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }
}
