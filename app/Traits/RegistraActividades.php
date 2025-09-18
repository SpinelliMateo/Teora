<?php

namespace App\Traits;

use App\Models\ActividadDashboard;
use Illuminate\Support\Facades\Auth;

trait RegistraActividades
{
    /**
     * Registra una actividad en el dashboard
     */
    protected function registrarActividad($descripcion, $tipo, $modulo = null, $referenciaId = null, $referenciaTipo = null, $datosAdicionales = null)
    {
        try {
            ActividadDashboard::registrar(
                Auth::id(),
                $descripcion,
                $tipo,
                $modulo,
                $referenciaId,
                $referenciaTipo,
                $datosAdicionales
            );
        } catch (\Exception $e) {
            // Log del error pero no interrumpir la operación principal
            \Log::error('Error registrando actividad: ' . $e->getMessage());
        }
    }

    /**
     * Registra actividad de carga
     */
    protected function registrarCarga($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'carga', $modulo, $referenciaId);
    }

    /**
     * Registra actividad de modificación
     */
    protected function registrarModificacion($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'modificacion', $modulo, $referenciaId);
    }

    /**
     * Registra actividad de creación
     */
    protected function registrarCreacion($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'creacion', $modulo, $referenciaId);
    }

    /**
     * Registra actividad de eliminación
     */
    protected function registrarEliminacion($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'eliminacion', $modulo, $referenciaId);
    }

    /**
     * Registra actividad de finalización
     */
    protected function registrarFinalizacion($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'finalizacion', $modulo, $referenciaId);
    }

    /**
     * Registra actividad de pausa
     */
    protected function registrarPausa($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'pausa', $modulo, $referenciaId);
    }

    /**
     * Registra actividad de reanudación
     */
    protected function registrarReanudacion($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'reanudacion', $modulo, $referenciaId);
    }

    /**
     * Registra actividad de actualización
     */
    protected function registrarActualizacion($descripcion, $modulo = null, $referenciaId = null)
    {
        $this->registrarActividad($descripcion, 'actualizacion', $modulo, $referenciaId);
    }
}