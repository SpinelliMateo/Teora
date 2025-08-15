<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ControlStockService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BarcodeController extends Controller
{
    private ControlStockService $controlStockService;

    public function __construct(ControlStockService $controlStockService)
    {
        $this->controlStockService = $controlStockService;
    }

    public function generate(int $controlStockId): Response
    {
        try {
            $controlStock = $this->controlStockService->obtenerConRelaciones($controlStockId);
            
            if (!$controlStock) {
                abort(404, 'Control stock no encontrado');
            }

            $codigo = $controlStock->codigo_barras ?: 'SERIE_' . $controlStockId;
            
            return $this->generarImagenCodigoBarras($codigo);
            
        } catch (\Exception $e) {
            Log::error("Error en generate(): " . $e->getMessage());
            return $this->generarImagenPlaceholder("ERROR_SERIE");
        }
    }

    public function generateModelo(int $controlStockId): Response
    {
        try {
            $controlStock = $this->controlStockService->obtenerConRelaciones($controlStockId);
            
            if (!$controlStock) {
                abort(404, 'Control stock no encontrado');
            }

            $codigo = 'SIN_MODELO';
            
            if ($controlStock->modelo) {
                $codigo = $controlStock->modelo->modelo ?? 
                         $controlStock->modelo->codigo_modelo ?? 
                         $controlStock->modelo->nombre_modelo ?? 
                         'MODELO_' . $controlStockId;
            }

            return $this->generarImagenCodigoBarras($codigo);
            
        } catch (\Exception $e) {
            Log::error("Error en generateModelo(): " . $e->getMessage());
            return $this->generarImagenPlaceholder("ERROR_MODELO");
        }
    }

    public function etiqueta(int $controlStockId): Response
    {
        return $this->generate($controlStockId);
    }

    public function etiquetaLargo(int $controlStockId): Response
    {
        try {
            $controlStock = $this->controlStockService->obtenerConRelaciones($controlStockId);
            
            if (!$controlStock) {
                abort(404, 'Control stock no encontrado');
            }

            $codigoLargo = ($controlStock->codigo_barras ?: 'SERIE') . '00' . str_pad($controlStock->n_serie ?: '0', 4, '0', STR_PAD_LEFT);
            
            return $this->generarImagenCodigoBarras($codigoLargo);
            
        } catch (\Exception $e) {
            Log::error("Error en etiquetaLargo(): " . $e->getMessage());
            return $this->generarImagenPlaceholder("ERROR_LARGO");
        }
    }

    private function generarImagenCodigoBarras(string $codigo): Response
    {
        try {
            // Intentar con la librería Picqer primero
            if (class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
                return $this->generarConPicqer($codigo);
            }
            
            // Si no está disponible, usar placeholder
            throw new \Exception("Librería de códigos de barras no disponible");
            
        } catch (\Exception $e) {
            Log::error("Error generando código de barras: " . $e->getMessage());
            return $this->generarImagenPlaceholder($codigo);
        }
    }

    private function generarConPicqer(string $codigo): Response
    {
        $codigoLimpio = preg_replace('/[^A-Za-z0-9\-_]/', '', $codigo);
        
        if (empty($codigoLimpio)) {
            $codigoLimpio = 'EMPTY_CODE';
        }

        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($codigoLimpio, $generator::TYPE_CODE_128, 3, 60);
        
        return response($barcode, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function generarImagenPlaceholder(string $codigo): Response
    {
        $width = 300;
        $height = 80;
        
        $image = imagecreate($width, $height);
        
        if (!$image) {
            abort(500, 'No se pudo crear la imagen');
        }
        
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $borderColor = imagecolorallocate($image, 200, 200, 200);
        
        imagefill($image, 0, 0, $bgColor);
        imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
        
        // Texto del código
        $text = substr($codigo, 0, 25);
        $textWidth = strlen($text) * 8;
        $x = max(5, ($width - $textWidth) / 2);
        
        imagestring($image, 3, $x, 25, $text, $textColor);
        imagestring($image, 2, $x, 45, "(Codigo de barras no disponible)", $textColor);
        
        // Líneas simulando código de barras
        for ($i = 0; $i < 30; $i++) {
            $x1 = 20 + ($i * 8);
            $lineHeight = ($i % 2 == 0) ? 15 : 10;
            imageline($image, $x1, 5, $x1, 5 + $lineHeight, $textColor);
        }
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache',
        ]);
    }
}