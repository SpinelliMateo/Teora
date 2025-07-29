<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ActividadServicioTecnico extends Model
{
    protected $table = 'actividades_servicio_tecnico';

    protected $fillable = [
        'servicio_tecnico_id',
        'user_id',
        'titulo',
        'descripcion',
    ];

    public function servicio_tecnico(){
        return $this->belongsTo(ServicioTecnico::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('d/m/Y')
        );
    }
}
