<?php


namespace Sledium\Testing;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase
{
    protected function getClient(bool $https = false, array $environment = []): TestClient
    {
        return new TestClient($https, $environment);
    }
}
