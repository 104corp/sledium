<?php


namespace Sledium\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

class Dummy1ServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('dummy1', function () {
            echo 'construct Dummy1';
            return new DummyForDependence1();
        });
    }
}
