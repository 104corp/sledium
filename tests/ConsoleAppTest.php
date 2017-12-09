<?php


namespace Sledium\Tests;

use PHPUnit\Framework\TestCase;
use Sledium\ConsoleApp;
use Sledium\Container;
use Sledium\Tests\Fixtures\DummyConsoleApp;
use Sledium\Tests\Fixtures\ErrorMakerConsoleApp;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleAppTest extends TestCase
{

    protected $tempBasePath;


    /**
     * @test
     */
    public function basicConsoleRun()
    {
        $input = new ArgvInput([]);
        $output = new BufferedOutput();
        $this->createApp()->run($input, $output);
        $fetched = $output->fetch();
        $this->assertRegExp("/Sledium Console App/", $fetched);
    }

    /**
     * @test
     */
    public function makeCommandShouldWork()
    {
        $output = new BufferedOutput();
        $this->createApp()->call('make:command', ['name' => 'CommandName'], $output);
        $this->assertRegExp("|Console command created successfully|", $output->fetch());
        $this->assertFileExists($this->tempBasePath . '/app/Console/Commands/CommandName.php');
    }

    /**
     * @test
     */
    public function makeJobShouldWork()
    {
        $output = new BufferedOutput();
        $this->createApp()->call('make:job', ['name' => 'JobName'], $output);
        $this->assertRegExp("|Job created successfully|", $output->fetch());
        $this->assertFileExists($this->tempBasePath . '/app/Jobs/JobName.php');
    }


    /**
     * @test
     */
    public function runEchoShouldWork()
    {
        $input = new ArgvInput([]);
        $output = new BufferedOutput();
        (new DummyConsoleApp(new Container($this->tempBasePath)))->run($input, $output);
        $out = $output->fetch();
        $this->assertRegExp("|dummy:command|", $out);
        (new DummyConsoleApp(new Container($this->tempBasePath)))->call('dummy:command', [], $output);
        $this->assertRegExp("|handle dummy command|", $output->fetch());
    }

    /**
     * @test
     */
    public function scheduleRunShouldWork()
    {
        $input = new ArgvInput([]);
        $output = new BufferedOutput();
        (new DummyConsoleApp(new Container($this->tempBasePath)))->run($input, $output);
        $out = $output->fetch();
        $this->assertRegExp("|schedule:run|", $out);
        $output = new BufferedOutput();
        (new DummyConsoleApp(new Container($this->tempBasePath)))->call('schedule:run', [], $output);
        $this->assertRegExp("|Running scheduled command|", $output->fetch());
    }

    /**
     * @test
     */
    public function consoleWillHandleException()
    {
        $input = new ArgvInput([]);
        $output = new BufferedOutput();
        (new ErrorMakerConsoleApp(new Container($this->tempBasePath)))->run($input, $output);
        $out = $output->fetch();
        $this->assertRegExp("|error-maker:exception|", $out);

        $output = new BufferedOutput();
        $app = new ErrorMakerConsoleApp(new Container($this->tempBasePath));
        $cbAsserted = false;
        $app->getContainer()->instance(
            'consoleErrorRenderer',
            $this->createRenderer(function (\Throwable $e, $o) use (&$cbAsserted, $output) {
                $this->assertInstanceOf(\Exception::class, $e);
                $this->assertTrue($o === $output);
                $cbAsserted = true;
            })
        );
        $app->run(new ArrayInput(['error-maker:exception']), $output);
        $this->assertTrue($cbAsserted);

        $app->getContainer()->forgetInstance('consoleErrorRenderer');
        $output = new BufferedOutput();
        $app->run(new ArrayInput(['error-maker:exception']), $output);
        $out = $output->fetch();
        $this->assertRegExp("|Exception|", $out);
    }


    /**
     * @test
     */
    public function consoleWillHandleError()
    {
        $input = new ArgvInput([]);
        $output = new BufferedOutput();
        (new ErrorMakerConsoleApp(new Container($this->tempBasePath)))->run($input, $output);
        $out = $output->fetch();
        $this->assertRegExp("|error-maker:error|", $out);

        $output = new BufferedOutput();
        $app = new ErrorMakerConsoleApp(new Container($this->tempBasePath));
        $cbAsserted = false;
        $app->getContainer()->instance(
            'consoleErrorRenderer',
            $this->createRenderer(function (\Throwable $e, $o) use (&$cbAsserted, $output) {
                $this->assertInstanceOf(\Error::class, $e);
                $this->assertTrue($o === $output);
                $cbAsserted = true;
            })
        );
        $app->run(new ArrayInput(['error-maker:error']), $output);
        $this->assertTrue($cbAsserted);

        $app->getContainer()->forgetInstance('consoleErrorRenderer');
        $output = new BufferedOutput();
        $app->run(new ArrayInput(['error-maker:error']), $output);
        $out = $output->fetch();
        $this->assertRegExp("|Error|", $out);
    }

    /**
     * @test
     */
    public function consoleWillHandleNativeError()
    {
        $input = new ArgvInput([]);
        $output = new BufferedOutput();
        (new ErrorMakerConsoleApp(new Container($this->tempBasePath)))->run($input, $output);
        $out = $output->fetch();
        $this->assertRegExp("|error-maker:native|", $out);

        $output = new BufferedOutput();
        $app = new ErrorMakerConsoleApp(new Container($this->tempBasePath));
        $cbAsserted = false;
        $app->getContainer()->instance(
            'consoleErrorRenderer',
            $this->createRenderer(function (\Throwable $e, $o) use (&$cbAsserted, $output) {
                $this->assertInstanceOf(\ErrorException::class, $e);
                $this->assertTrue($o === $output);
                $cbAsserted = true;
            })
        );
        $app->run(new ArrayInput(['error-maker:native']), $output);
        $this->assertTrue($cbAsserted);

        $app->getContainer()->forgetInstance('consoleErrorRenderer');
        $output = new BufferedOutput();
        $app->run(new ArrayInput(['error-maker:native']), $output);
        $out = $output->fetch();
        $this->assertRegExp("|Error|", $out);
    }

    public function setUp()
    {
        parent::setUp();
        $this->tempBasePath = sys_get_temp_dir() . '/' . (string)random_int(10000, 999999);
        mkdir($this->tempBasePath, 0777, true);
        exec('cp -R ' . __DIR__ . '/Fixtures/resources/ConsoleAppTest/* ' . $this->tempBasePath, $o);
        if (!is_dir($this->tempBasePath . '/config') || !is_file($this->tempBasePath . '/composer.json')) {
            $this->markTestSkipped('Test base path can not be made');
        }
    }

    public function tearDown()
    {
        exec('rm -rf ' . $this->tempBasePath);
        parent::tearDown();
    }

    protected function createApp()
    {
        $container = new Container($this->tempBasePath);
        return new ConsoleApp($container);
    }

    protected function createRenderer(\Closure $callback)
    {
        $handler = new class
        {
            public $cb;

            public function render(\Throwable $e, $o)
            {
                ($this->cb)($e, $o);
            }
        };
        $handler->cb = $callback;
        return $handler;
    }
}
