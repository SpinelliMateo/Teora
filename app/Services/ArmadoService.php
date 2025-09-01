<?php

namespace App\Services;

use App\Contracts\ControlStockRepositoryInterface;
use App\Contracts\OperarioRepositoryInterface;
use App\Models\ControlStock;
use App\Models\Operario;
use App\Models\ProcesosOperarios;
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
                'message' => '❌ El producto con serie ' . $numeroSerie . ' no está en condiciones para ser armado.',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Producto válido para armado',
            'data' => $controlStock->load('modelo')
        ];
    }

    public function validarMotorParaArmado(string $numeroMotor): array
    {
        $controlStock = $this->controlStockRepository->findByNumeroMotor($numeroMotor);

        if ($controlStock) {
            return [
                'success' => false,
                'message' => '❌ No se puede usar el motor con el número ' . $numeroMotor . ' porque ya lo está usando el número de serie ' . $controlStock->n_serie . '.',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Motor válido para armado'
        ];
    }
    public function validarOperarioParaArmado(string $operarioCodigo): array
    {
        $operarios = $this->operarioRepository->getOperariosArmadores();

        if (!$operarios) {
            return [
                'success' => false,
                'message' => '❌ El operario con el código ' . $operarioCodigo . ' no es un armador válido.',
                'data' => null
            ];
        }else{
            $operario = $operarios->firstWhere('codigo_qr', $operarioCodigo);
            if (!$operario) {
                return [
                    'success' => false,
                    'message' => '❌ El operario con el código ' . $operarioCodigo . ' no es un armador válido.',
                    'data' => null
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Operario válido para armado',
            'data' => $operario
        ];
    }

    public function procesarArmado(string $numeroSerie, string $numeroMotor, string $operarioCodigo): array
    {
        $controlStock = $this->controlStockRepository->findByNumeroSerie($numeroSerie);

        if (!$controlStock || !$this->isValidForArmado($controlStock)) {
            return [
                'success' => false,
                'message' => 'El producto no está en condiciones para ser armado.'
            ];
        }

        $this->marcarComoArmado($controlStock, $numeroMotor, $operarioCodigo);

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
                DB::raw("CONCAT(operarios.nombre, ' ', operarios.apellido) as operario"),
                DB::raw('COUNT(*) as cantidad_armados')
            )
            ->groupBy('operarios.id', 'operarios.nombre', 'operarios.apellido')
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

    private function marcarComoArmado(ControlStock $controlStock, string $numeroMotor, string $operarioCodigo): void
    {
        DB::transaction(function () use ($controlStock, $numeroMotor, $operarioCodigo) {
            $controlStock->update([
                'equipo' => $numeroMotor,
                'fecha_armado' => Carbon::now()
            ]);

            $registro = ProcesosOperarios::where('control_stock_id', $controlStock->id);
            $operario = Operario::where('codigo_qr', $operarioCodigo)->first();
            $registro->update([
                'operario_armador_id' => $operario->id,
                'updated_at' => Carbon::now()
            ]);
        });
    }
}