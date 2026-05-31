<?php

namespace App\Providers;

use App\Repositories\Contracts\ConsumerRepositoryInterface;
use App\Repositories\Contracts\RequestLogRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\EloquentConsumerRepository;
use App\Repositories\EloquentRequestLogRepository;
use App\Repositories\EloquentServiceRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ConsumerRepositoryInterface::class, EloquentConsumerRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);
        $this->app->bind(RequestLogRepositoryInterface::class, EloquentRequestLogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
