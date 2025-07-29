<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Operario extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected $appends = ['nombre_completo'];

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre . ' ' . $this->apellido;
    }

    public function ordenesFabricacion(): BelongsToMany
    {
        return $this->belongsToMany(OrdenFabricacion::class, 'orden_fabricacion_operarios');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function procesos(){
        return $this->hasMany(ProcesosOperarios::class);
    }
}