<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesosOperarios extends Model
{
    protected $table = 'procesos_operarios';

    protected $fillable = [
        'control_stock_id',
        'operario_armador_id',
        'operario_prearmador_id',
        'operario_embalador_id',
    ];

    public function control_stock(){
        return $this->belongsTo(ControlStock::class);
    }

    public function operario_armador(){
        return $this->belongsTo(Operario::class, 'operario_armador_id');
    }

    public function operario_prearmador(){
        return $this->belongsTo(Operario::class, 'operario_prearmador_id');
    }

    public function operario_embalador(){
        return $this->belongsTo(Operario::class, 'operario_embalador_id');
    }
}
