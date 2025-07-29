<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Str;
use Carbon\Carbon;

class MigrarUsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios= DB::table('usuarios')->get(); 

        $data=[]; 
        $emailsAgregados = []; 

        foreach($usuarios as $usuario){

            if(!is_null ($usuario->email) && !in_array($usuario->email, $emailsAgregados)){
                $emailsAgregados[] = $usuario->email; 

                $data[]= [
                    'id' => $usuario->ID, 
                    'name'=> $usuario->username,
                    'email'=> $usuario->email,
                    'password' => $usuario->password,
                    'email_verified_at'=> now(),
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]; 
            }
        }

        User::insert($data); 
    }
}
