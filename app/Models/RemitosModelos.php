<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemitosModelos extends Model
{
    protected $table = 'remitos_modelos';

    protected $fillable = [
        'remito_id',
        'modelo_id',
        'cantidad',
    ];

    public function remito()
    {
        return $this->belongsTo(Remito::class);
    }

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }
}
