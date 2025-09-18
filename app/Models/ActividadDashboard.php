<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ActividadDashboard extends Model
{
    use HasFactory;

    protected $table = 'actividades_dashboard';
    
    protected $fillable = [
        'user_id',
        'descripcion',
        'tipo',
        'modulo',
        'referencia_id',
        'referencia_tipo',
        'datos_adicionales'
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope para obtener actividades recientes
    public function scopeRecientes($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Scope para filtrar por tipo
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Scope para filtrar por usuario
    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope para filtrar por módulo
    public function scopePorModulo($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    // Accessor para fecha formateada
    public function getFechaFormateadaAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    // Método para registrar una nueva actividad
    public static function registrar($userId, $descripcion, $tipo, $modulo = null, $referenciaId = null, $referenciaTipo = null, $datosAdicionales = null)
    {
        return self::create([
            'user_id' => $userId,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'modulo' => $modulo,
            'referencia_id' => $referenciaId,
            'referencia_tipo' => $referenciaTipo,
            'datos_adicionales' => $datosAdicionales
        ]);
    }

    // Método para obtener el icono según el tipo
    public function getIconoAttribute()
    {
        $iconos = [
            'carga' => 'upload',
            'modificacion' => 'edit',
            'pausa' => 'pause',
            'reanudacion' => 'play',
            'finalizacion' => 'check-circle',
            'eliminacion' => 'trash',
            'creacion' => 'plus-circle',
            'actualizacion' => 'refresh'
        ];

        return $iconos[$this->tipo] ?? 'activity';
    }

    // Método para obtener el color según el tipo
    public function getColorAttribute()
    {
        $colores = [
            'carga' => 'blue',
            'modificacion' => 'yellow',
            'pausa' => 'orange',
            'reanudacion' => 'green',
            'finalizacion' => 'emerald',
            'eliminacion' => 'red',
            'creacion' => 'indigo',
            'actualizacion' => 'purple'
        ];

        return $colores[$this->tipo] ?? 'gray';
    }
}