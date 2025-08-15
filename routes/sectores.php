<?php

use App\Http\Controllers\Operarios\DespachoController;
use Illuminate\Support\Facades\Route;

Route::prefix('operarios')->name('operarios.')->group(function () {
    Route::get('/despacho', [DespachoController::class, 'index'])->name('despacho.index');
    Route::post('/despacho/buscar-control-stock', [DespachoController::class, 'buscarControlStock'])->name('despacho.buscar-control-stock');
    Route::post('/despacho/modelos-remitos', [DespachoController::class, 'obtenerModelosRemitos'])->name('despacho.modelos-remitos');
    Route::post('/despacho/procesar', [DespachoController::class, 'procesarDespacho'])->name('despacho.procesar');
});
