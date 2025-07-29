<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ServicioTecnico extends Model
{
    protected $table = 'servicio_tecnico';

    protected $fillable = [
        'modelo_id',
        'factura',
        'user_id', //fk hacia el usuario de rol tecnico o admin
        'serie',
        'fecha_salida',
        'estado',
        'pagado',
        'razon_social',
        'dni_cuit',
        'cliente_distribuidor',
        'contacto',
        'vendedor',
        'direccion',
        'provincia',
        'localidad',
        'telefono',
        'email',
        'horarios',
        'problema_id',
        'subproblema_id',
        'interno_externo',
        'reinc',
        'importe',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function modelo(){
        return $this->belongsTo(Modelo::class);
    }

    public function actividades(){
        return $this->hasMany(ActividadServicioTecnico::class);
    }

    public function problema(){
        return $this->belongsTo(Problema::class);
    }

    public function subproblema(){
        return $this->belongsTo(SubProblema::class);
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('d/m/Y')
        );
    }

    protected function fechaSalida(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('d/m/Y')
        );
    }
}
