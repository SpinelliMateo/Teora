<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlStock extends Model
{
    protected $table = 'control_stock';

    protected $fillable = [
        'modelo_id',
        'orden_fabricacion_id', 
        'n_serie',
        'fecha_prearmado',
        'fecha_inyectado',
        'fecha_armado',
        'fecha_embalado',
        'fecha_salida',
        'oculto',
        'equipo',
    ];

    protected $casts = [
        'fecha_salida' => 'datetime',
        'fecha_embalado' => 'datetime', 
        'fecha_armado' => 'datetime',
        'fecha_inyectado' => 'datetime',
        'fecha_prearmado' => 'datetime',
    ];

    // Accessor para código de barras basado en n_serie si no existe campo específico
    public function getCodigoBarrasAttribute(): string
    {
        return $this->attributes['codigo_barras'] ?? $this->n_serie;
    }

    public function ordenFabricacion()
    {
        return $this->belongsTo(OrdenFabricacion::class);
    }

    public function prearmadores()
    {
        return $this->belongsToMany(
            Operario::class,
            'procesos_operarios',
            'control_stock_id',
            'operario_prearmador_id'
        );
    }

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    public function procesos()
    {
        return $this->hasMany(ProcesosOperarios::class);
    }

    public function remitos()
    {
        return $this->belongsToMany(
            Remito::class,
            'controlstock_remitos',
            'control_stock_id',
            'remito_id'
        );
    }
}