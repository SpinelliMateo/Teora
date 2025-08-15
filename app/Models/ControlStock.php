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

    protected $casts = [
        'fecha_prearmado' => 'datetime',
        'fecha_inyectado' => 'datetime',
        'fecha_armado' => 'datetime',
    ];

    public function ordenFabricacion()
    {
        return $this->belongsTo(OrdenFabricacion::class);
    }

    public function modelo(){
        return $this->belongsTo(Modelo::class);
    }

    public function procesos(){
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

    public function scopeEnPrearmado($query)
    {
        return $query->whereNull('fecha_prearmado');
    }
    
    public function scopeEnInyectado($query)
    {
        return $query->whereNotNull('fecha_prearmado')
        ->whereNull('fecha_inyectado');
    }
    
    public function scopeEnArmado($query)
    {
        return $query->whereNotNull('fecha_inyectado')
        ->whereNull('fecha_armado');
    }
    
    public function scopeCompletado($query)
    {
        return $query->whereNotNull('fecha_armado');
    }

}
