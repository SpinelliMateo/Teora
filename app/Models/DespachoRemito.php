<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DespachoRemito extends Model
{
    protected $table = 'despachos_remitos';

    protected $fillable = ['despacho_id', 'remito_id'];

    public function despacho()
    {
        return $this->belongsTo(DespachoFinalizado::class, 'despacho_id');
    }

    public function remito()
    {
        return $this->belongsTo(Remito::class, 'remito_id');
    }
}
