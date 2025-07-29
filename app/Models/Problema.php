<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Problema extends Model
{
    protected $table = 'problemas';

    protected $fillable = [
        'nombre',
    ];

    public function subproblemas(){
        return $this->hasMany(SubProblema::class);
    }

    public function servicio_tecnico(){
        return $this->hasOne(ServicioTecnico::class);
    }
}
