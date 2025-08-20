<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ControlStockService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Picqer\Barcode\BarcodeGeneratorPNG;

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

    /**
     * Generar etiqueta completa de embalado similar a la imagen proporcionada
     */
    public function etiqueta(int $controlStockId): Response
    {
        try {
            $controlStock = $this->controlStockService->obtenerConRelaciones($controlStockId);
            
            if (!$controlStock) {
                abort(404, 'Control stock no encontrado');
            }

            return $this->generarEtiquetaEmbalado($controlStock);
            
        } catch (\Exception $e) {
            Log::error("Error en etiqueta(): " . $e->getMessage());
            return $this->generarEtiquetaPlaceholder();
        }
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

    /**
     * Generar etiqueta HTML completa para embalado
     */
    private function generarEtiquetaEmbalado($controlStock): Response
    {
        $fechaEmbalado = $controlStock->fecha_embalado 
            ? Carbon::parse($controlStock->fecha_embalado)->format('d-m-Y')
            : Carbon::now()->format('d-m-Y');
            
        $modelo = $controlStock->modelo->nombre_modelo ?? 
                  $controlStock->modelo->modelo ?? 
                  'SIN MODELO';
                  
        $numeroSerie = $controlStock->n_serie ?? 'SIN SERIE';
        
        // Generar código de barras
        $codigoBarras = $this->generarCodigoBarrasBase64($numeroSerie);

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Etiqueta de Embalado</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                
                body {
                    font-family: Arial, sans-serif;
                    background: white;
                    padding: 10px;
                }
                
                .etiqueta {
                    width: 300px;
                    border: 2px solid black;
                    background: white;
                    font-size: 12px;
                }
                
                .fila {
                    display: flex;
                    border-bottom: 1px solid black;
                }
                
                .fila:last-child {
                    border-bottom: none;
                }
                
                .label {
                    background: #f0f0f0;
                    padding: 8px 10px;
                    font-weight: bold;
                    border-right: 1px solid black;
                    width: 120px;
                    text-align: left;
                }
                
                .value {
                    padding: 8px 10px;
                    flex: 1;
                    font-weight: bold;
                }
                
                .barcode-container {
                    text-align: center;
                    padding: 15px 10px;
                    background: white;
                }
                
                .barcode-img {
                    max-width: 250px;
                    height: 60px;
                }
                
                .barcode-text {
                    font-family: monospace;
                    font-size: 10px;
                    margin-top: 5px;
                    letter-spacing: 2px;
                }
                
                @media print {
                    body { 
                        margin: 0;
                        padding: 5px;
                    }
                    .etiqueta {
                        border: 1px solid black;
                    }
                }
            </style>
        </head>
        <body>
            <div class="etiqueta">
                <div class="fila">
                    <div class="label">Ing. a depósito:</div>
                    <div class="value">' . $fechaEmbalado . '</div>
                </div>
                
                <div class="fila">
                    <div class="label">Modelo:</div>
                    <div class="value">' . htmlspecialchars($modelo) . '</div>
                </div>
                
                <div class="fila">
                    <div class="label">Serie:</div>
                    <div class="value">' . htmlspecialchars($numeroSerie) . '</div>
                </div>
                
                <div class="barcode-container">
                    <img src="' . $codigoBarras . '" class="barcode-img" alt="Código de barras" />
                    <div class="barcode-text">' . htmlspecialchars($numeroSerie) . '</div>
                </div>
            </div>
            
            <script>
                // Auto-imprimir al cargar
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            </script>
        </body>
        </html>';

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Cache-Control' => 'no-cache'
        ]);
    }

    /**
     * Generar código de barras en base64
     */
    private function generarCodigoBarrasBase64(string $codigo): string
    {
        try {
            if (class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
                $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                $codigoLimpio = preg_replace('/[^A-Za-z0-9\-_]/', '', $codigo);
                
                if (empty($codigoLimpio)) {
                    $codigoLimpio = 'EMPTY_CODE';
                }
                
                $barcode = $generator->getBarcode($codigoLimpio, $generator::TYPE_CODE_128, 3, 60);
                return 'data:image/png;base64,' . base64_encode($barcode);
            }
            
            return $this->generarCodigoBarrasPlaceholderBase64($codigo);
            
        } catch (\Exception $e) {
            Log::error("Error generando código de barras: " . $e->getMessage());
            return $this->generarCodigoBarrasPlaceholderBase64($codigo);
        }
    }

    /**
     * Generar código de barras placeholder en base64
     */
    private function generarCodigoBarrasPlaceholderBase64(string $codigo): string
    {
        $width = 250;
        $height = 60;
        
        $image = imagecreate($width, $height);
        
        if (!$image) {
            return '';
        }
        
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $borderColor = imagecolorallocate($image, 200, 200, 200);
        
        imagefill($image, 0, 0, $bgColor);
        imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
        
        // Simular barras
        for ($i = 0; $i < 40; $i++) {
            $x1 = 10 + ($i * 6);
            $lineHeight = ($i % 3 == 0) ? 40 : 30;
            $y1 = ($height - $lineHeight) / 2;
            imageline($image, $x1, $y1, $x1, $y1 + $lineHeight, $textColor);
        }
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    /**
     * Generar etiqueta placeholder HTML
     */
    private function generarEtiquetaPlaceholder(): Response
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Error - Etiqueta de Embalado</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    padding: 20px; 
                    text-align: center; 
                }
                .error { 
                    color: red; 
                    border: 2px solid red; 
                    padding: 20px; 
                    margin: 20px; 
                }
            </style>
        </head>
        <body>
            <div class="error">
                <h3>Error al generar etiqueta</h3>
                <p>No se pudo cargar la información del producto</p>
            </div>
        </body>
        </html>';

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Cache-Control' => 'no-cache'
        ]);
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

        $generator = new BarcodeGeneratorPNG();
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