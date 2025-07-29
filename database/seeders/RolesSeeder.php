<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    // Crear roles
    $admin = Role::firstOrCreate(['name' => 'admin']);
    $encargado = Role::firstOrCreate(['name' => 'encargado']);
    $supervisor = Role::firstOrCreate(['name' => 'supervisor tecnico']);
    $tecnico = Role::firstOrCreate(['name' => 'tecnico']);

    // Crear permisos
    $inicio = Permission::firstOrCreate(['name' => 'inicio']);
    $verOrdenes = Permission::firstOrCreate(['name' => 'ver ordenes']);
    $gestionarOrdenes = Permission::firstOrCreate(['name' => 'gestionar ordenes']);
    $verStock = Permission::firstOrCreate(['name' => 'ver stock']);
    $gestionarStock = Permission::firstOrCreate(['name' => 'gestionar stock']);
    $verServicioTecnico = Permission::firstOrCreate(['name' => 'ver servicio tecnico']);
    $gestionarServicioTecnico = Permission::firstOrCreate(['name' => 'gestionar servicio tecnico']);
    $verProceso = Permission::firstOrCreate(['name' => 'ver servicio proceso']);
    $gestionarProceso = Permission::firstOrCreate(['name' => 'gestionar servicio proceso']);
    $alertas = Permission::firstOrCreate(['name' => 'alertas']);
    $reportes = Permission::firstOrCreate(['name' => 'repotes']);
    $verRemitos = Permission::firstOrCreate(['name' => 'ver remitos']);
    $gestionarRemitos = Permission::firstOrCreate(['name' => 'gestionar remitos']);
    $configuracion = Permission::firstOrCreate(['name' => 'configuracion']);

    // Asignar permisos a los roles
    $admin->syncPermissions([
        $inicio,
        $verOrdenes,
        $gestionarOrdenes,
        $verStock,
        $gestionarStock,
        $verServicioTecnico,
        $gestionarServicioTecnico,
        $verProceso,
        $gestionarProceso,
        $alertas,
        $reportes,
        $verRemitos,
        $gestionarRemitos,
        $configuracion
    ]);

    $encargado->syncPermissions([
        $inicio,
        $verOrdenes,
        $verOrdenes,
        $verStock,
        $gestionarStock,
        $verServicioTecnico,
        $gestionarServicioTecnico,
        $verProceso,
        $gestionarProceso,
        $alertas,
        $reportes,
        $verRemitos,
        $gestionarRemitos,
        $configuracion
    ]);

    $supervisor->syncPermissions([
        $inicio,
        $verServicioTecnico,
        $gestionarServicioTecnico,
        $alertas,
    ]);

    $tecnico->syncPermissions([
        $verServicioTecnico,
    ]);
}
}
