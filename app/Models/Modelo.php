<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    protected $table = 'modelos';

    protected $fillable = [
        'modelo',
        'nombre_modelo',
        'tension',
        'frecuencia',
        'corriente',
        'potencia',
        'aislacion',
        'sistema',
        'volumen',
        'espumante',
        'clase',
        'gas',
        'cantidad_gas',
    ];

    public function ordenesFabricacion()
    {
        return $this->belongsToMany(OrdenFabricacion::class, 'modelo_orden_fabricacion')
        ->withPivot('cantidad')
        ->withTimestamps();
    }

    public function stock(){
        return $this->hasMany(ControlStock::class);
    }

    public function stock_minimo(){
        return $this->hasOne(StockMinimo::class);
    }

    public function sercicio_tecnico(){
        return $this->hasOne(ServicioTecnico::class);
    }
    public function remitos()
    {
        return $this->belongsToMany(Remito::class, 'remitos_modelos')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
}
