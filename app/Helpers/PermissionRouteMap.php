<?php

namespace App\Helpers;

class PermissionRouteMap
{
    // Mapa de permisos a nombres de rutas
    public static function map(): array
    {
        return [
            'inicio' => 'dashboard',
            'ver ordenes' => 'ordenes-fabricacion.index',
            'gestionar ordenes' => 'ordenes-fabricacion.index',
            'stock' => 'stock',
            'ver servicio tecnico' => 'servicio_tecnico',
            'gestionar servicio tecnico' => 'servicio_tecnico',
            'ver servicio proceso' => 'procesos',
            'gestionar servicio proceso' => 'procesos',
            'alertas' => 'alertas',
            'reportes' => 'reportes',
            'ver remitos' => 'remitos',
            'gestionar remitos' => 'remitos',
            'historial de despachos' => 'historial',
            'configuracion' => 'configuracion',
        ];
    }
}