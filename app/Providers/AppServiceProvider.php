<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blaze::optimize()->in(resource_path('views/components'));
    }
}
