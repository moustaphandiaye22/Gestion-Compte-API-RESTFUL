<?php

namespace App\Providers;

use App\Services\SmsServiceInterface;
use App\Services\TwilioSmsService;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
         $this->app->bind(ResponseFactoryContract::class, ResponseFactory::class);
         $this->app->bind(SmsServiceInterface::class, TwilioSmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
