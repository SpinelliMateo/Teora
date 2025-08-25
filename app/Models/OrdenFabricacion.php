<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrdenFabricacion extends Model
{
    protected $table = 'ordenes_fabricacion';
    
    protected $fillable = ['fecha', 'fecha_finalizacion', 'no_orden', 'estado'];
    
    public function operarios()
    {
        return $this->belongsToMany(Operario::class, 'orden_fabricacion_operarios');
    }
    
    public function modelos()
    {
        return $this->belongsToMany(Modelo::class, 'modelo_orden_fabricacion')
        ->withPivot('cantidad');
    }
    
    public function controlStock()
    {
        return $this->hasMany(ControlStock::class);
    }
    
   
}