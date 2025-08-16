<?php

namespace App\Services;

use App\Contracts\ControlStockRepositoryInterface;
use App\Contracts\OperarioRepositoryInterface;
use App\Models\ControlStock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArmadoService
{
    private ControlStockRepositoryInterface $controlStockRepository;
    private OperarioRepositoryInterface $operarioRepository;

    public function __construct(
        ControlStockRepositoryInterface $controlStockRepository,
        OperarioRepositoryInterface $operarioRepository
    ) {
        $this->controlStockRepository = $controlStockRepository;
        $this->operarioRepository = $operarioRepository;
    }

    public function validarProductoParaArmado(string $numeroSerie): array
    {
        $controlStock = $this->controlStockRepository->findByNumeroSerie($numeroSerie);

        if (!$controlStock) {
            return [
                'success' => false,
                'message' => '❌ No se encontró el registro con número de serie: ' . $numeroSerie,
                'data' => null
            ];
        }

        if (!$this->isValidForArmado($controlStock)) {
            return [
                'success' => false,
                'message' => '⚠️ El producto con serie ' . $numeroSerie . ' no está en condiciones para ser armado.',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Producto válido para armado',
            'data' => $controlStock->load('modelo')
        ];
    }

    public function procesarArmado(string $numeroSerie, string $numeroMotor, int $operarioId): array
    {
        $controlStock = $this->controlStockRepository->findByNumeroSerie($numeroSerie);

        if (!$controlStock || !$this->isValidForArmado($controlStock)) {
            return [
                'success' => false,
                'message' => 'El producto no está en condiciones para ser armado.'
            ];
        }

        $this->marcarComoArmado($controlStock, $numeroMotor, $operarioId);

        return [
            'success' => true,
            'message' => '✅ Producto N°' . $numeroSerie . ' armado exitosamente.'
        ];
    }

    public function obtenerEstadisticasArmadoHoy(): array
    {
        $hoy = Carbon::today();
        
        $armadosHoy = DB::table('operarios')
            ->join('procesos_operarios', 'operarios.id', '=', 'procesos_operarios.operario_armador_id')
            ->join('control_stock', 'procesos_operarios.control_stock_id', '=', 'control_stock.id')
            ->whereDate('control_stock.fecha_armado', $hoy)
            ->whereNotNull('control_stock.fecha_armado')
            ->select(
                'operarios.nombre as operario',
                DB::raw('COUNT(*) as cantidad_armados')
            )
            ->groupBy('operarios.id', 'operarios.nombre')
            ->orderByDesc('cantidad_armados')
            ->get();

        $totalArmados = $armadosHoy->sum('cantidad_armados');

        return [
            'operarios' => $armadosHoy,
            'total' => $totalArmados
        ];
    }

    public function obtenerOperariosArmadores(): Collection
    {
        return $this->operarioRepository->getOperariosArmadores();
    }

    private function isValidForArmado(ControlStock $controlStock): bool
    {
        return !is_null($controlStock->fecha_inyectado) && 
               is_null($controlStock->fecha_armado);
    }

    private function marcarComoArmado(ControlStock $controlStock, string $numeroMotor, int $operarioId): void
    {
        DB::transaction(function () use ($controlStock, $numeroMotor, $operarioId) {
            // Actualizar control_stock
            $controlStock->update([
                'equipo' => $numeroMotor,
                'fecha_armado' => Carbon::now()
            ]);

            // Crear registro en procesos_operarios
            DB::table('procesos_operarios')->insert([
                'control_stock_id' => $controlStock->id,
                'operario_armador_id' => $operarioId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });
    }
}