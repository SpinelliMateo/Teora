<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remito extends Model
{
    use HasFactory;

    protected $table = 'remitos';

    protected $fillable = [
        'n_remito',
        'numero',
        'estado',
        'cliente',
        'fecha_despacho',
    ];

    protected $casts = [
        'fecha_despacho' => 'datetime',
    ];

    public function modelos()
    {
        return $this->belongsToMany(Modelo::class, 'remitos_modelos')
        ->withPivot('cantidad')
        ->withTimestamps();
    }

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_DESPACHADO = 'despachado';
    public const ESTADO_CANCELADO = 'cancelado';
    public function controlStock()
    {
        return $this->belongsToMany(
            ControlStock::class,       
            'controlstock_remitos',    
            'remito_id',              
            'control_stock_id'         
        );
    }

}
