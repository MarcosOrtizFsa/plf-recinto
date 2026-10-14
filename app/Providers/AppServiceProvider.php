<?php

namespace App\Providers;

use App\Contracts\AlmacenamientoDocumentos;
use App\Services\Documentos\AlmacenamientoPrivadoDocumentos;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AlmacenamientoDocumentos::class, AlmacenamientoPrivadoDocumentos::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
