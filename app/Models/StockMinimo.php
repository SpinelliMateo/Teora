<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMinimo extends Model
{
    protected $table = 'stock_minimo';

    protected $fillable = [
        'modelo_id',
        'stock_minimo',
    ];

    public function modelo(){
        return $this->belongsTo(Modelo::class);
    }

}
