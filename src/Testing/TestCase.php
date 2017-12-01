<?php


namespace Sledium\Testing;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Sledium\App;

abstract class TestCase extends PhpUnitTestCase
{
    protected function getClient(bool $https = false, array $environment = []): TestClient
    {
        return new TestClient($this->createHttpApp(), $https, $environment);
    }
    abstract protected function createHttpApp():App;
}
