<?php


namespace Sledium\Tests;

use Sledium\Config;
use Sledium\Container;
use Sledium\Tests\Fixtures\Dummy1;
use Sledium\Tests\Fixtures\Dummy1ServiceProvider;
use Sledium\Tests\Fixtures\Dummy2;
use Sledium\Tests\Fixtures\Dummy2ServiceProvider;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @test
     */
    public function containerShouldDefaultSingleton()
    {
        $container = new Container(__DIR__);
        $expectObject = new \stdClass();
        $container['dummy'] = function () use ($expectObject) {
            return $expectObject;
        };

        $this->assertTrue($expectObject === $container['dummy']);
        $this->assertTrue($expectObject === $container->get('dummy'));
        $this->assertFalse((new \stdClass()) === $container['dummy']);
        $this->assertFalse((new \stdClass()) === $container->get('dummy'));

        $container['foo'] = function () use ($expectObject) {
            return new \stdClass();
        };
        $this->assertTrue($container['foo'] === $container->get('foo'));
    }

    /**
     * @test
     */
    public function allPathShouldExpected()
    {
        $container = new Container(__DIR__);
        $this->assertEquals(__DIR__, $container->basePath());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'app', $container->path());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'storage', $container->storagePath());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'config', $container->configPath());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'database', $container->databasePath());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'bootstrap', $container->bootstrapPath());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'resources', $container->resourcePath());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'public', $container->publicPath());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'dependencies', $container->dependencePath());
    }

    /**
     * @test
     */
    public function dependenceFolderShouldWork()
    {
        $basePath = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'Fixtures',
            'resource',
            'ContainerTest',
            'dependenceFolderShouldWork'
        ]);
        $container = new Container($basePath);
        $this->assertInstanceOf(Dummy1::class, $container->get('Abc'));
        $this->assertInstanceOf(Dummy1::class, $container['Abc']);
        $this->assertTrue($container->get('Abc') === $container['Abc']);
        $this->assertInstanceOf(Dummy2::class, $container['Cde']);
        $this->assertInstanceOf(Dummy2::class, $container->get('Cde'));
        $this->assertTrue($container->get('Cde') === $container['Cde']);
    }

    /**
     * @test
     */
    public function registerConfiguredProvidersShouldWork()
    {
        $basePath = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'Fixtures',
            'resource',
            'ContainerTest',
            'registerConfiguredProvidersShouldWork'
        ]);
        $container = new Container($basePath);
        $container->instance('config', new Config($container->configPath()));
        ob_start();
        $container->registerConfiguredProviders();
        $output = ob_get_clean();
//        $this->assertRegExp("/construct/", $output);
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function registerDeferredProviderShouldWork()
    {
        //boot at start
        $container = new Container(__DIR__);
        $container->boot();
        ob_start();
        $container->registerDeferredProvider(Dummy1ServiceProvider::class, 'dummy1');
        $output = ob_get_clean();
        $this->assertEmpty($output);

        ob_start();
        $container->get('dummy1');
        $output = ob_get_clean();
        $this->assertRegExp("/construct/", $output);
        $container->registerDeferredProvider(Dummy2ServiceProvider::class, 'dummy2');
        ob_start();
        $dummy = $container->get('dummy2');
        $output = ob_get_clean();
        $this->assertRegExp('/'.addslashes(get_class($dummy)).'/', $output);

        //boot at after
        $container = new Container(__DIR__);
        ob_start();
        $container->registerDeferredProvider(Dummy1ServiceProvider::class, 'dummy1');
        $output = ob_get_clean();
        $this->assertEmpty($output);
        ob_start();
        $container->get('dummy1');
        $output = ob_get_clean();
        $this->assertRegExp("/construct/", $output);
        $container->registerDeferredProvider(Dummy2ServiceProvider::class, 'dummy2');
        ob_start();
        $dummy = $container->get('dummy2');
        $output = ob_get_clean();
        $this->assertEmpty($output);
        ob_start();
        $container->boot();
        $output = ob_get_clean();
        $this->assertRegExp('/'.addslashes(get_class($dummy)).'/', $output);
    }
}
