<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\Operarios\InyectadoController;
use App\Http\Controllers\Operarios\DespachoController;
use App\Http\Controllers\Operarios\PrearmadoController;
use App\Http\Controllers\Operarios\ArmadoController;
use App\Http\Controllers\Operarios\EmbaladoController;
use Illuminate\Support\Facades\Route;

Route::prefix('operarios')->name('operarios.')->group(function () {

    Route::get('/despacho', [DespachoController::class, 'index'])->name('despacho.index');
    Route::post('/despacho/buscar-control-stock', [DespachoController::class, 'buscarControlStock'])->name('despacho.buscar-control-stock');
    Route::post('/despacho/modelos-remitos', [DespachoController::class, 'obtenerModelosRemitos'])->name('despacho.modelos-remitos');
    Route::post('/despacho/procesar', [DespachoController::class, 'procesarDespacho'])->name('despacho.procesar');

    Route::get('/prearmado', [PrearmadoController::class, 'index'])->name('sector.prearmado');
    Route::get('/prearmado/{operario}/modelos', [PrearmadoController::class, 'obtenerModelosPendientes']);
    Route::post('/prearmado', [PrearmadoController::class, 'store']);
    Route::get('/prearmado/{id}/detalle', [PrearmadoController::class, 'detalle'])->name('prearmado.detalle');
    Route::get('/prearmado/{id}/etiqueta', [PrearmadoController::class, 'etiqueta'])->name('sector.prearmado.etiqueta');
    Route::post('/imprimir/zebra', [PrearmadoController::class, 'imprimirZebra'])->name('imprimir.zebra');

    Route::get('/inyectado', [InyectadoController::class, 'index']);
    Route::post('/inyectado', [InyectadoController::class, 'store']);

    Route::get('/armado', [ArmadoController::class, 'index'])->name('armado.index');
    Route::post('/armado/validar', [ArmadoController::class, 'validarProducto'])->name('armado.validar');
    Route::get('/armado/operarios', [ArmadoController::class, 'obtenerOperarios'])->name('armado.operarios');
    Route::post('/armado', [ArmadoController::class, 'store'])->name('armado.store');

    // Rutas de Embalado
    Route::get('/embalado', [EmbaladoController::class, 'index'])->name('embalado.index');
    Route::post('/embalado/validar-productos', [EmbaladoController::class, 'validarProductos'])->name('embalado.validar-productos');
    Route::get('/embalado/operarios', [EmbaladoController::class, 'obtenerOperarios'])->name('embalado.operarios');
    Route::post('/embalado', [EmbaladoController::class, 'store'])->name('embalado.store');
    Route::post('/embalado/confirmar', [EmbaladoController::class, 'confirmarEmbalado'])->name('embalado.confirmar');
   
    Route::get('/embalado/etiquetas', [EmbaladoController::class, 'etiquetas'])->name('embalado.etiquetas');
    Route::post('/embalado/reimprimir-etiqueta', [EmbaladoController::class, 'reimprimirEtiqueta'])->name('embalado.reimprimir-etiqueta');
});// En la sección de rutas de Embalado, agregar:
