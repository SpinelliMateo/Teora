<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DespachoFinalizado extends Model
{
    protected $table = 'despachos_finalizados';

    protected $fillable = ['numero_despacho'];
    
    public function remitos()
    {
        return $this->belongsToMany(Remito::class, 'despachos_remitos', 'despacho_id', 'remito_id');
    }
}
