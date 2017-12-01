<?php
require __DIR__ . '/../../../../../vendor/autoload.php';

$container = new \Sledium\Container(__DIR__.'/../');

$app = new Sledium\App($container);

$container->activeIlluminateFacades();

include __DIR__.'/../routes/index.php';

return $app;
