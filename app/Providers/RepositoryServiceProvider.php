<?php

namespace App\Providers;

use App\Contracts\ControlStockRepositoryInterface;
use App\Contracts\RemitoRepositoryInterface;
use App\Repositories\ControlStockRepository;
use App\Repositories\RemitoRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider{

    public function register(){

        $this->app->bind(
            RemitoRepositoryInterface::class,
            RemitoRepository::class,
        ); 

        $this->app->bind(
            ControlStockRepositoryInterface::class,
            ControlStockRepository::class
        );

        
    }

    public function boot(){

    }

}