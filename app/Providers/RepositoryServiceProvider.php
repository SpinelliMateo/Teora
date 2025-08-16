<?php

namespace App\Providers;

use App\Contracts\ControlStockRepositoryInterface;
use App\Contracts\OperarioRepositoryInterface;
use App\Contracts\RemitoRepositoryInterface;
use App\Repositories\ControlStockRepository;
use App\Repositories\OperarioRepository;
use App\Repositories\RemitoRepository;
use App\Services\InyectadoService;
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

        $this->app->bind(
            OperarioRepositoryInterface::class,
            OperarioRepository::class
        );

    }

    public function boot(){

    }

}