<?php


namespace Sledium\Testing;

use Helmich\JsonAssert\JsonAssertions;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Sledium\App;

abstract class TestCase extends PhpUnitTestCase
{
    use JsonAssertions;

    private $client;

    public function tearDown()
    {
        $this->client = null;
    }

    protected function getClient(bool $https = false, array $environment = [], bool $createNew = false): TestClient
    {
        if (null === $this->client || $createNew) {
            $this->client = new TestClient($this->createHttpApp(), $https, $environment);
        }
        return $this->client;
    }
    abstract protected function createHttpApp():App;
}
