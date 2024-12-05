<?php

namespace App\Providers;

use DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::listen(function ($query) {
            print_r($query->sql);
            print_r($query->bindings);

            // This will output the query and its bindings
            // logger();
            // logger($query->bindings);
        });
    }
}
