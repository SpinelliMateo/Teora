<?php

use App\Http\Controllers\OrdenFabricacionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMinimoController;
use App\Http\Controllers\ServicioTecnicoController;
use App\Http\Controllers\ProblemasController;
use App\Http\Controllers\ProcesosOperariosController;
use App\Http\Controllers\RemitosController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // STOCK
    Route::middleware(['permission:stock'])->group(function () {
        Route::get('stock', [StockController::class, 'index'])->name('stock');
        Route::get('stock-detalle', [StockController::class, 'stock_detalle'])->name('stock_detalle');
        Route::put('ocultar_stock_detalle', [StockController::class, 'ocultar_stock_detalle'])->name('ocultar_stock_detalle');
    });

    // ORDENES DE FABRICACION
    Route::middleware(['permission:ver ordenes|gestionar ordenes'])->group(function () {
        Route::resource('ordenes-fabricacion', OrdenFabricacionController::class);
        Route::post('/ordenes-fabricacion/{id}/modelos', [OrdenFabricacionController::class, 'agregarModelo'])->name('ordenes-fabricacion.agregar-modelo');
        Route::put('/ordenes-fabricacion/{ordenId}/modelos/{modeloId}', [OrdenFabricacionController::class, 'actualizarModelo'])->name('ordenes-fabricacion.actualizar-modelo');
        Route::delete('/ordenes-fabricacion/{ordenId}/modelos/{modeloId}', [OrdenFabricacionController::class, 'eliminarModelo'])->name('ordenes-fabricacion.eliminar-modelo');
    });

    // CONFIGURACION, USUARIOS, ROLES, STOCK MINIMO, PROBLEMAS
    Route::middleware(['permission:configuracion'])->group(function () {
        Route::get('configuracion', function () {
            return Inertia::render('configuracion/Configuracion');
        })->name('configuracion');

        //Usuarios
        Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios');
        Route::post('usuarios/create', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('usuarios/update/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('usuarios/delete/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

        // Roles
        Route::get('roles', [RolController::class, 'index'])->name('roles');
        Route::post('roles/create', [RolController::class, 'store'])->name('roles.store');
        Route::put('roles/{id}', [RolController::class, 'update'])->name('roles.update');
        Route::delete('roles/{id}', [RolController::class, 'destroy'])->name('roles.destroy');
        
        // Stock minimo
        Route::get('stock-minimo', [StockMinimoController::class, 'index'])->name('stock_minimo');
        Route::put('update_stock_minimo', [StockMinimoController::class, 'update_stock_minimo'])->name('update_stock_minimo');

        // Problemas
        Route::get('problemas', [ProblemasController::class, 'index'])->name('problemas');
        Route::get('get_subproblemas_by_id', [ProblemasController::class, 'get_subproblemas_by_id'])->name('get_subproblemas_by_id');
        Route::post('create_problema', [ProblemasController::class, 'create_problema'])->name('create_problema');
        Route::put('update_problema', [ProblemasController::class, 'update_problema'])->name('update_problema');
        Route::delete('delete_problema', [ProblemasController::class, 'delete_problema'])->name('delete_problema');
    });

    // SERVICIO TECNICO
    Route::middleware(['permission:ver servicio tecnico|gestionar servicio tecnico'])->group(function () {
        Route::get('servicio-tecnico', [ServicioTecnicoController::class, 'index'])->name('servicio_tecnico');
        Route::get('stock_by_n_serie', [ServicioTecnicoController::class, 'stock_by_n_serie'])->name('stock_by_n_serie');
        Route::get('servicio-tecnico-detalle', [ServicioTecnicoController::class, 'servicio_tecnico_detalle'])->name('servicio_tecnico_detalle');
        Route::middleware(['permission:gestionar servicio tecnico'])->group(function () {
            Route::post('create_servicio_tecnico', [ServicioTecnicoController::class, 'create_servicio_tecnico'])->name('create_servicio_tecnico');
            Route::put('update_servicio_pagado', [ServicioTecnicoController::class, 'update_servicio_pagado'])->name('update_servicio_pagado');
            Route::put('update_servicio_estado', [ServicioTecnicoController::class, 'update_servicio_estado'])->name('update_servicio_estado');
            Route::put('update_servicio_tecnico', [ServicioTecnicoController::class, 'update_servicio_tecnico'])->name('update_servicio_tecnico');
        });
        Route::post('create_actividad_servicio_tecnico', [ServicioTecnicoController::class, 'create_actividad_servicio_tecnico'])->name('create_actividad_servicio_tecnico');
        Route::put('update_actividad_servicio_tecnico', [ServicioTecnicoController::class, 'update_actividad_servicio_tecnico'])->name('update_actividad_servicio_tecnico');
    });

    // SEGUIMIENTO POR PROCESOS
    Route::middleware(['permission:ver servicio proceso|gestionar servicio proceso'])->group(function () {
        Route::get('seguimiento-por-proceso', [ProcesosOperariosController::class, 'index'])->name('procesos');
        Route::middleware(['permission:gestionar servicio proceso'])->group(function () {    
            Route::put('update_proceso', [ProcesosOperariosController::class, 'update_proceso'])->name('update_proceso');
        });
    });

    //REPORTES
    Route::middleware(['permission:reportes'])->group(function () {
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes');
    });
    
    //REMITOS
    Route::middleware(['permission:ver remitos|gestionar remitos'])->group(function () {
        Route::get('remitos', [RemitosController::class, 'index'])->name('remitos');
        Route::middleware(['permission:gestionar remitos'])->group(function () {
            Route::post('remitos/create', [RemitosController::class, 'store'])->name('remitos.store');
            Route::put('remitos/update/{id}', [RemitosController::class, 'update'])->name('remitos.update');
            Route::delete('remitos/{id}', [RemitosController::class, 'destroy'])->name('remitos.destroy');
            Route::put('remitos/despachar/{id}', [RemitosController::class, 'updateDespachado'])->name('remitos.despachado');
        });
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
