<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrdenFabricacion extends Model
{
    protected $table = 'ordenes_fabricacion';

    protected $fillable = [
        'fecha',
        'no_orden',
        'fecha_finalizacion',
    ];

    protected $casts = [
        'fecha'               => 'date:Y-m-d',
        'fecha_finalizacion'  => 'date:Y-m-d',
        'no_orden' => 'string',
    ];

    public function operarios() : BelongsToMany{
        return $this->belongsToMany(
            Operario::class, 
            'orden_fabricacion_operarios',
            'orden_fabricacion_id',
            'operario_id'
        );         
    }

    public function modelos() : BelongsToMany
    {
        return $this->belongsToMany(
            Modelo::class, 
            'modelo_orden_fabricacion',
            'orden_fabricacion_id',
            'modelo_id'
        )
        ->withPivot('cantidad')
        ->withTimestamps();
    }
}