<?php


namespace Sledium\Tests;

use Mockery;
use Sledium\App;
use Sledium\Exceptions\HttpNotAcceptableException;
use Sledium\Exceptions\HttpUnprocessableEntityException;
use Sledium\Testing\TestCase;

class SlediumAppTest extends TestCase
{

    /**
     * @test
     */
    public function phpNonFatalErrorShouldHandle()
    {
        $client = $this->getClient();
        $app = $client->getApp();
        $app->get('/foo', function () {
            include 'not_exist_file';
        });
        $container = $app->getContainer();
        $client->get('/foo')->assertStatus(500);
        unset($container['errorHandler']);
        $this->expectException(\ErrorException::class);
        $client->get('/foo');
    }

    /**
     * @test
     */
    public function shutdownFunctionShouldHandleError()
    {
        $stub = Mockery::mock(App::class.'[isFatalError]')->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $stub->shouldReceive('isFatalError')->andReturn(true);
        $this->assertInstanceOf(App::class, $stub);
        $container = $stub->getContainer();
        $shutDownFunction = $container->get('shoutDownFunction');
        $this->assertInstanceOf(\Closure::class, $shutDownFunction);
        set_error_handler(function () {
            return false;
        });
        unset($container['errorHandler']);
        $errorMessage = 'triggered error';
        $container->instance('errorHandler', function ($request, $response, $error) use ($errorMessage) {
            $this->assertInstanceOf(\ErrorException::class, $error);
            $this->assertEquals($errorMessage, $error->getMessage());
            return $response;
        });
        trigger_error($errorMessage);
        $shutDownFunction();
    }

    /**
     * @test
     */
    public function phpErrorExceptionHandler()
    {
        $client = $this->getClient();
        $app = $client->getApp();
        $container = $app->getContainer();
        $uncaughtExceptionHandler = set_exception_handler(function () {
        });
        $this->assertInstanceOf(\Closure::class, $uncaughtExceptionHandler);

        $exception = new \Exception('test exception');
        $container->instance('errorHandler', function ($request, $response, $error) use ($exception) {
            $this->assertInstanceOf(\Exception::class, $error);
            $this->assertTrue($error === $exception);
            return $response;
        });
        $uncaughtExceptionHandler($exception);
        unset($container['errorHandler']);
        $this->expectExceptionObject($exception);
        $this->expectExceptionMessage('test exception');
        $uncaughtExceptionHandler($exception);
    }

    /**
     * @test
     */
    public function errorRendererShouldDetermineAccept()
    {
        $client = $this->getClient();
        $app = $client->getApp();
        $app->getContainer()['settings']['displayErrorDetails'] = true;
        $app->get('/foo', function () {
            throw new \Exception('test error');
        });
        $app->get('/bar', function () {
            throw new HttpNotAcceptableException('test error');
        });
        $app->post('/bar', function () {
            throw new HttpUnprocessableEntityException('test error', ['Content-Type'=>'application/json']);
        });
        $response = $client->get('/foo', ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $this->assertRegExp('|application/json|', $response->getHeaderLine('Content-Type'));

        $response = $client->get('/bar', ['Accept' => 'application/json']);
        $response->assertStatus(406);
        $this->assertRegExp('|application/json|', $response->getHeaderLine('Content-Type'));


        $response = $client->get('/foo', ['Accept' => 'application/void+json']);
        $response->assertStatus(500);
        $this->assertRegExp('|application/json|', $response->getHeaderLine('Content-Type'));

        $response = $client->get('/bar', ['Accept' => 'application/void+json']);
        $response->assertStatus(406);
        $this->assertRegExp('|application/json|', $response->getHeaderLine('Content-Type'));

        $response = $client->get('/foo', ['Accept' => 'application/void+xml']);
        $response->assertStatus(500);
        $this->assertRegExp('|application/xml|', $response->getHeaderLine('Content-Type'));

        $response = $client->get('/bar', ['Accept' => 'application/void+xml']);
        $response->assertStatus(406);
        $this->assertRegExp('|application/xml|', $response->getHeaderLine('Content-Type'));

        $response = $client->get('/foo', ['Accept' => 'text/plain']);
        $response->assertStatus(500);
        $this->assertRegExp('|text/html|', $response->getHeaderLine('Content-Type'));

        $response = $client->get('/bar', ['Accept' => 'text/plain']);
        $response->assertStatus(406);
        $this->assertRegExp('|text/html|', $response->getHeaderLine('Content-Type'));

        $response = $client->post('/bar', ['Accept' => 'text/xml']);
        $response->assertStatus(422);
        $this->assertRegExp('|application/json|', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     */
    public function autoOptionsShouldDetermineAccept()
    {
        $client = $this->getClient();
        $app = $client->getApp();
        $container = $app->getContainer();
        $container['settings']['defaultContentType'] = 'application/json';
        $app->getContainer()['settings']['autoHandleOptionsMethod'] = true;
        $app->get('/foo', function () {
        });

        $response = $client->options('/foo', ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $this->assertRegExp('|application/json|', $response->getHeaderLine('Content-Type'));
        $this->assertRegExp('|GET|', $response->getHeaderLine('Allow'));

        $response = $client->options('/foo', ['Accept' => 'text/xml']);
        $response->assertStatus(200);
        $this->assertRegExp('|text/xml|', $response->getHeaderLine('Content-Type'));
        $this->assertRegExp('|GET|', $response->getHeaderLine('Allow'));

        $response = $client->options('/foo', ['Accept' => 'text/html']);
        $response->assertStatus(200);
        $this->assertRegExp('|text/html|', $response->getHeaderLine('Content-Type'));
        $this->assertRegExp('|GET|', $response->getHeaderLine('Allow'));

        $response = $client->options('/foo', ['Accept' => 'application/foo']);
        $response->assertStatus(200);
        $this->assertRegExp('|application/json|', $response->getHeaderLine('Content-Type'));
        $this->assertRegExp('|GET|', $response->getHeaderLine('Allow'));
    }

    /**
     * @test
     */
    public function autoOptionsMethodOn()
    {
        $this->runSettingsTestCase(
            ['autoHandleOptionsMethod' => true],
            ['GET'],
            'OPTIONS',
            '',
            [],
            200,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * @test
     */
    public function autoOptionsMethodOnWithDetermineRouteBeforeAppMiddleware()
    {
        $this->runSettingsTestCase(
            ['autoHandleOptionsMethod' => true, 'determineRouteBeforeAppMiddleware' => true],
            ['GET'],
            'OPTIONS',
            '',
            [],
            200,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * @test
     */
    public function autoOptionsMethodOff()
    {
        $this->runSettingsTestCase(
            ['autoHandleOptionsMethod' => false],
            ['GET'],
            'OPTIONS',
            '',
            [],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * @test
     */
    public function autoOptionsMethodOffWithDetermineRouteBeforeAppMiddleware()
    {
        $this->runSettingsTestCase(
            ['autoHandleOptionsMethod' => false, 'determineRouteBeforeAppMiddleware' => true],
            ['GET'],
            'OPTIONS',
            '',
            [],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * @test
     */
    public function notAllowedMethodRequest()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => false],
            ['GET'],
            'POST',
            '',
            [],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * @test
     */
    public function notAllowedMethodRequestWithDetermineRouteBeforeAppMiddleware()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => true],
            ['GET'],
            'POST',
            '',
            [],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function methodOverride()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => false],
            ['GET'],
            'POST',
            '',
            ['X-Http-Method-Override' => 'GET'],
            200
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function methodOverrideWithDetermineRouteBeforeAppMiddleware()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => true],
            ['GET'],
            'POST',
            '',
            ['X-Http-Method-Override' => 'GET'],
            200
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function notAllowedMethodOverride()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => false],
            ['GET'],
            'POST',
            '',
            ['X-Http-Method-Override' => 'DELETE'],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function notAllowedMethodOverrideWithDetermineRouteBeforeAppMiddleware()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => true],
            ['GET'],
            'POST',
            '',
            ['X-Http-Method-Override' => 'DELETE'],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function invalidAllowedMethodOverride()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => false],
            ['GET'],
            'POST',
            '',
            ['X-Http-Method-Override' => 'INVALID METHOD'],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function invalidAllowedMethodOverrideWithDetermineRouteBeforeAppMiddleware()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => true],
            ['GET'],
            'POST',
            '',
            ['X-Http-Method-Override' => 'INVALID METHOD'],
            405,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function invalidAllowedMethodOverrideOriginalOptionsMethod()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => false],
            ['GET'],
            'OPTIONS',
            '',
            ['X-Http-Method-Override' => 'INVALID METHOD'],
            200,
            ['Allow' => "/GET/"]
        );
    }

    /**
     * X-Http-Method-Override
     * @test
     */
    public function invalidAllowedMethodOverrideOriginalOptionsMethodWithDetermineRouteBeforeAppMiddleware()
    {
        $this->runSettingsTestCase(
            ['determineRouteBeforeAppMiddleware' => true],
            ['GET'],
            'OPTIONS',
            '',
            ['X-Http-Method-Override' => 'INVALID METHOD'],
            200,
            ['Allow' => "/GET/"]
        );
    }

    protected function runSettingsTestCase(
        array $settings = [],
        array $routesMethods = ['GET'],
        string $requestMethod = 'GET',
        string $rawBody = '',
        array $requestHeaders = [],
        int $expectedStatus = 200,
        array $expectedRegExpHeaders = []
    ) {
        $client = $this->getClient();
        $app = $client->getApp();
        $container = $app->getContainer();
        foreach ($settings as $setting => $value) {
            $container['settings'][$setting] = $value;
        }
        $path = '/foo';
        $app->map($routesMethods, $path, function () {
            return func_get_arg(1);
        });
        $response = $client->request($requestMethod, $path, $rawBody, $requestHeaders);
        $response->assertStatus($expectedStatus);
        foreach ($expectedRegExpHeaders as $header => $pattern) {
            $this->assertRegExp($pattern, $response->getHeaderLine($header));
        };
        $client->request($requestMethod, '/not_exist', $rawBody, $requestHeaders)
            ->assertStatus('404');
    }


    public static function setupBeforeClass()
    {
        defined('APP_BASE_PATH') or define('APP_BASE_PATH', __DIR__ . '/Fixtures/resources/AppTest');
    }

    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    protected function createHttpApp(): App
    {
        return new App();
    }
}
