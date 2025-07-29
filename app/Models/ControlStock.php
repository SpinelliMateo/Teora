<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlStock extends Model
{
    protected $table = 'control_stock';

    protected $fillable = [
        'modelo_id',
        'n_serie',
        'fecha_prearmado',
        'fecha_inyectado',
        'fecha_armado',
        'fecha_embalado',
        'fecha_salida',
        'oculto',
        'equipo',
    ];

    public function modelo(){
        return $this->belongsTo(Modelo::class);
    }

    public function procesos(){
        return $this->hasMany(ProcesosOperarios::class);
    }

}
