<?php


namespace Sledium\Tests;

use Illuminate\Log\Writer;
use PHPUnit\Framework\TestCase;
use Sledium\Config;
use Sledium\Container;
use Sledium\ServiceProviders\LogServiceProvider;

class LogServiceProviderTest extends TestCase
{
    /** @var  string */
    private $logDir;
    /** @var  Container */
    private $container;
    /**
     * @test
     */
    public function registerLogServiceProviderShouldOk()
    {
        $container = new Container($this->getFixtureBasePath());
        $container->instance('config', new Config($container->configPath()));
        $container->register(LogServiceProvider::class);
        $logger = $container['log'];
        $this->assertInstanceOf(Writer::class, $logger);
    }

    /**
     * @test
     */
    public function loggerShouldWorks()
    {
        $container = $this->container;
        $logDir = $this->logDir;
        $container->registerDeferredProvider(LogServiceProvider::class, 'log');
        /** @var Writer $logger */
        $logger = $container['log'];
        $message = 'sledium logger test';
        $logger->debug($message);
        $this->assertFileExists($logDir. DIRECTORY_SEPARATOR. 'sledium.log');
        $this->assertRegExp(
            "/".addslashes($message)."/",
            file_get_contents($logDir. DIRECTORY_SEPARATOR. 'sledium.log')
        );
    }

    /**
     * @test
     */
    public function defaultHandlerShouldWorksWhenNoConfiguredHandlers()
    {
        $container = $this->container;
        $loggerConfig = [
            'channel' => 'Test',
            'default_level' => 'debug',
            'max_files' => 5, //
        ];
        file_put_contents(
            $container->configPath().DIRECTORY_SEPARATOR.'logger.php',
            "<?php\nreturn " . var_export($loggerConfig, true).";"
        );
        $container->instance('config', new Config($container->configPath()));
        $this->loggerShouldWorks();
    }


    public function setUp()
    {
        $basePath = $this->getTempDir();
        $container = new Container($this->getTempDir());
        $this->container = $container;
        if (file_exists($basePath)) {
            $this->deleteDirectory($basePath);
        }
        $logDir = $container->storagePath().DIRECTORY_SEPARATOR.'logs';
        mkdir($container->basePath(), 0777);
        mkdir($container->configPath(), 0777);
        mkdir($logDir, 0777, true);
        $this->logDir = $logDir;
        $container->instance('config', new Config($container->configPath()));
    }

    public function tearDown()
    {
        $this->container = null;
        $this->deleteDirectory($this->getTempDir());
    }

    private function getFixtureBasePath($path = null)
    {
        $basePath = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'Fixtures',
            'resources',
            'LogServiceProviderTest'
        ]);
        return realpath($basePath . ($path === null ? '' : DIRECTORY_SEPARATOR . $path));
    }

    private function getTempDir()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'LogServiceProviderTest';
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}
