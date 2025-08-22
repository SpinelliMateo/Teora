<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::create([
        //     'name' => 'franco',
        //     'email' => 'franco@gmail.com',
        //     'password' => Hash::make('franco'),
        // ]);
        // $this->call(MigrarUsuariosSeeder::class); 
        // $this->call(MigrarModelosSeeder::class);
        // $this->call(MigrarControlStockSeeder::class);
        //$this->call(OperariosSeeder::class);
        //$this->call(ProcesosSeeder::class);
        $this->call(SectorSeeder::class);
    }
}
