<?php
// routes/sectores.php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\Operarios\InyectadoController;
use App\Http\Controllers\Operarios\DespachoController;
use App\Http\Controllers\Operarios\PrearmadoController;
use App\Http\Controllers\Operarios\ArmadoController;
use App\Http\Controllers\Operarios\EmbaladoController;
use App\Http\Controllers\Operarios\Auth\OperarioAuthController;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación de operarios (sin middleware)
Route::prefix('operarios')->name('operarios.')->group(function () {
    Route::get('/login', [OperarioAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [OperarioAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [OperarioAuthController::class, 'logout'])->name('logout');
});

// Rutas protegidas de operarios (con middleware)
Route::prefix('operarios')->name('operarios.')->middleware(['operario_sector'])->group(function () {

    // Despacho
    Route::middleware(['operario_sector:despacho'])->group(function () {
        Route::get('/despacho', [DespachoController::class, 'index'])->name('despacho.index');
        Route::post('/despacho/buscar-control-stock', [DespachoController::class, 'buscarControlStock'])->name('despacho.buscar-control-stock');
        Route::post('/despacho/modelos-remitos', [DespachoController::class, 'obtenerModelosRemitos'])->name('despacho.modelos-remitos');
        Route::post('/despacho/procesar', [DespachoController::class, 'procesarDespacho'])->name('despacho.procesar');
    });

    // Prearmado
    Route::middleware(['operario_sector:prearmado'])->group(function () {
        Route::get('/prearmado', [PrearmadoController::class, 'index'])->name('sector.prearmado');
        Route::get('/prearmado/{operario}/modelos', [PrearmadoController::class, 'obtenerModelosPendientes']);
        Route::post('/prearmado', [PrearmadoController::class, 'store']);
    });

    // Inyectado
    Route::middleware(['operario_sector:inyectado'])->group(function () {
        Route::get('/inyectado', [InyectadoController::class, 'index'])->name('inyectado.index');
        Route::post('/inyectado', [InyectadoController::class, 'store']);
    });

    // Armado
    Route::middleware(['operario_sector:armado'])->group(function () {
        Route::get('/armado', [ArmadoController::class, 'index'])->name('armado.index');
        Route::post('/armado/validar', [ArmadoController::class, 'validarProducto'])->name('armado.validar');
        Route::post('/armado/validar-step-2', [ArmadoController::class, 'validarMotor'])->name('armado.validar-motor');
        Route::post('/armado/validar-operario', [ArmadoController::class, 'validarOperario'])->name('armado.validar-operario');
        Route::post('/armado', [ArmadoController::class, 'store'])->name('armado.store');
    });

    // Embalado
    Route::middleware(['operario_sector:embalado'])->group(function () {
        Route::get('/embalado', [EmbaladoController::class, 'index'])->name('embalado.index');
        Route::post('/embalado/validar-productos', [EmbaladoController::class, 'validarProductos'])->name('embalado.validar-productos');
        Route::get('/embalado/operarios', [EmbaladoController::class, 'obtenerOperarios'])->name('embalado.operarios');
        Route::post('/embalado', [EmbaladoController::class, 'store'])->name('embalado.store');
        Route::post('/embalado/confirmar', [EmbaladoController::class, 'confirmarEmbalado'])->name('embalado.confirmar');
        Route::get('/embalado/etiquetas', [EmbaladoController::class, 'etiquetas'])->name('embalado.etiquetas');
        Route::post('/embalado/reimprimir-etiqueta', [EmbaladoController::class, 'reimprimirEtiqueta'])->name('embalado.reimprimir-etiqueta');
    });
});