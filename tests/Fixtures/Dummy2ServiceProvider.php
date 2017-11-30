<?php


namespace Sledium\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

class Dummy2ServiceProvider extends ServiceProvider
{
    public function boot()
    {
        echo get_class($this->app['dummy2']);
    }
    public function register()
    {
        $this->app->singleton('dummy2', function () {
            return new Dummy2();
        });
    }
}
