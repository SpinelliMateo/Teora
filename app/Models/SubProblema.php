<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProblema extends Model
{
    protected $table = 'subproblemas';

    protected $fillable = [
        'nombre',
        'problema_id',
    ];

    public function problema(){
        return $this->belongsTo(Problema::class);
    }

    public function servicio_tecnico(){
        return $this->hasOne(ServicioTecnico::class);
    }
}
