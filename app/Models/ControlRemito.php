<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlRemito extends Model
{
    protected $table = 'controlstock_remitos';

    protected $fillable = [
        'remito_id',
        'control_stock_id',
    ];

    public function remito()
    {
        return $this->belongsTo(Remito::class, 'remito_id');
    }
    public function controlStock()
    {
        return $this->belongsTo(ControlStock::class, 'control_stock_id');
    }
}
