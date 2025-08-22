<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operario extends Model
{
    use HasFactory;

    protected $table = 'operarios';

    protected $fillable = [
        'nombre',
        'apellido', 
        'n_legajo',
        'codigo_qr',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['nombre_completo'];

    public function ordenesFabricacion(): BelongsToMany
    {
        return $this->belongsToMany(OrdenFabricacion::class, 'orden_fabricacion_operarios');
    }

    public function sectores(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'operario_sectores')->withTimestamps();
    }

    public function procesos(): HasMany
    {
        return $this->hasMany(ProcesosOperarios::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('apellido', 'like', "%{$termino}%")
              ->orWhere('n_legajo', 'like', "%{$termino}%");
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($operario) {
            // Solo generar código QR automáticamente
            if (empty($operario->codigo_qr)) {
                $operario->codigo_qr = self::generarCodigoQrUnico();
            }
            
            // El n_legajo debe ser proporcionado manualmente
            // Validar que sea único se hace en las validaciones del controller
        });
    }

    private static function generarCodigoQrUnico(): string
    {
        do {
            $codigo = 'OP' . substr(md5(uniqid(rand(), true)), 0, 12);
        } while (self::where('codigo_qr', $codigo)->exists());

        return $codigo;
    }

    // Método helper para validar legajo único
    public static function validarLegajoUnico(string $legajo, ?int $operarioId = null): bool
    {
        $query = self::where('n_legajo', $legajo);
        
        if ($operarioId) {
            $query->where('id', '!=', $operarioId);
        }
        
        return !$query->exists();
    }

    // Método para generar sugerencia de legajo
    public static function sugerirLegajo(): string
    {
        $ultimoOperario = self::orderBy('id', 'desc')->first();
        $numeroLegajo = $ultimoOperario ? $ultimoOperario->id + 1 : 1;

        do {
            $legajo = 'LEG' . str_pad($numeroLegajo, 4, '0', STR_PAD_LEFT);
            $numeroLegajo++;
        } while (self::where('n_legajo', $legajo)->exists());

        return $legajo;
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre . ' ' . ($this->apellido ?? ''));
    }

    public function getSectoresNombresAttribute(): string
    {
        return $this->sectores->pluck('nombre')->join(', ') ?: 'Sin sectores';
    }
}