<?php
use Sledium\Container;

return function (Container $container) {
    return new \Sledium\Tests\Fixtures\DummyForDependence1();
};
