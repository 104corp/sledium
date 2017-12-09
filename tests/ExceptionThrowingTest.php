<?php


namespace Sledium\Tests;

use Sledium\App;
use Sledium\Container;
use Sledium\Exceptions\HttpException;
use Sledium\Exceptions\HttpClientException;
use Sledium\Exceptions\HttpBadRequestException;
use Sledium\Exceptions\HttpUnauthorizedException;
use Sledium\Exceptions\HttpPaymentRequiredException;
use Sledium\Exceptions\HttpForbiddenException;
use Sledium\Exceptions\HttpNotFoundException;
use Sledium\Exceptions\HttpMethodNotAllowedException;
use Sledium\Exceptions\HttpNotAcceptableException;
use Sledium\Exceptions\HttpUnprocessableEntityException;
use Sledium\Exceptions\HttpPreconditionRequiredException;
use Sledium\Exceptions\HttpTooManyRequestsException;
use Sledium\Exceptions\HttpUnsupportedMediaTypeException;



use Sledium\Testing\TestCase;

class ExceptionThrowingTest extends TestCase
{
    protected $tempBasePath;


    /**
     * @test
     */
    public function httpClientExceptionStatusMust4xx()
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpClientException(500, 'Internal server error');
    }

    /**
     * @test
     */
    public function throwHttpBadRequestException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpBadRequestException()
        );
    }

    /**
     * @test
     */
    public function throwHttpUnauthorizedException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpUnauthorizedException()
        );
    }

    /**
     * @test
     */
    public function throwHttpPaymentRequiredException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpPaymentRequiredException()
        );
    }

    /**
     * @test
     */
    public function throwHttpForbiddenException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpForbiddenException()
        );
    }

    /**
     * @test
     */
    public function throwHttpNotFoundException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpNotFoundException()
        );
    }

    /**
     * @test
     */
    public function throwHttpMethodNotAllowedException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpMethodNotAllowedException()
        );
    }
    /**
     * @test
     */
    public function throwHttpNotAcceptableException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpNotAcceptableException()
        );
    }
    /**
     * @test
     */
    public function throwHttpUnprocessableEntityException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpUnprocessableEntityException()
        );
    }

    /**
     * @test
     */
    public function throwHttpPreconditionRequiredException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpPreconditionRequiredException()
        );
    }

    /**
     * @test
     */
    public function throwHttpTooManyRequestsException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpTooManyRequestsException()
        );
    }

    /**
     * @test
     */
    public function throwHttpUnsupportedMediaTypeException()
    {
        $this->throwHttpExceptionClientWillGetPredefinedStatusCode(
            new HttpUnsupportedMediaTypeException()
        );
    }



    protected function throwHttpExceptionClientWillGetPredefinedStatusCode(HttpException $exception)
    {
        $client = $this->getClient();
        $app = $client->getApp();
        $app->get('/foo', function () use ($exception) {
            throw $exception;
        });
        $response = $client->get('/foo');
        $response->assertStatus($exception->getStatusCode());
    }

    public function setUp()
    {
        parent::setUp();
        $this->tempBasePath = sys_get_temp_dir() . '/' . (string)random_int(10000, 999999);
        mkdir($this->tempBasePath . '/config', 0777, true);
        if (!is_dir($this->tempBasePath . '/config')) {
            $this->markTestSkipped('Test base path can not be made');
        }
    }

    public function tearDown()
    {
        exec('rm -rf ' . $this->tempBasePath);
        parent::tearDown();
    }

    protected function createHttpApp(): App
    {
        return new App(new Container($this->tempBasePath));
    }
}
