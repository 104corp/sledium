<?php


namespace Sledium\Testing;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sledium\App;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;

/**
 * Class Client
 * @package Corp104\Apim\Test\SlimTest
 * @method TestResponse get(string $path, array $headers = [])
 * @method TestResponse delete(string $path, array $headers = [])
 * @method TestResponse head(string $path, array $headers = [])
 * @method TestResponse options(string $path, array $headers = [])
 * @method TestResponse post(string $path, array $data = [], array $headers = [])
 * @method TestResponse patch(string $path, array $data = [], array $headers = [])
 * @method TestResponse put(string $path, array $data = [], array $headers = [])
 * @method TestResponse postJson(string $path, array $data, array $headers = [])
 * @method TestResponse patchJson(string $path, array $data, array $headers = [])
 * @method TestResponse putJson(string $path, array $data, array $headers = [])
 * @method TestResponse postRaw(string $path, string $data, array $headers = [])
 * @method TestResponse patchRaw(string $path, string $data, array $headers = [])
 * @method TestResponse putRaw(string $path, string $data, array $headers = [])
 */
class TestClient
{
    /** @var  App */
    private $app;
    /** @var bool */
    private $https = false;

    /** @var array */
    private $env = [];


    /**
     * TestClient constructor.
     * @param App $app
     * @param bool $https
     * @param array $env
     */
    public function __construct(App $app, bool $https = false, array $env = [])
    {

        $this->app = $app;
        $this->setHttps($https);
        $this->setEnv($env);
    }

    public function setHttps(bool $https)
    {
        $this->https = $https;
    }

    public function setEnv(array $env)
    {
        $this->env = $env;
    }

    public function getApp()
    {
        return $this->app;
    }

    public function __call($name, $arguments)
    {
        if (preg_match("/Raw$/", $name)) { //xxxRaw
            $this->validateArgument($name, $arguments, ['*string', '*string', 'array']);
            $method = strtoupper(substr($name, 0, -3));
            $path = $arguments[0];
            $rawBody = $arguments[1];
            $headers = isset($arguments[2]) ? $arguments[2] : [];
        } elseif (preg_match("/Json$/", $name)) {
            $this->validateArgument($name, $arguments, ['*string', '*array', 'array']);
            $method = strtoupper(substr($name, 0, -4));
            $path = $arguments[0];
            $rawBody = json_encode($arguments[1]);
            $headers = array_merge(
                ['Content-Type' => 'application/json'],
                isset($arguments[2]) ? $arguments[2] : []
            );
        } else {
            $this->validateArgument($name, $arguments, ['*string', 'array', 'array']);
            $method = strtoupper($name);
            $path = $arguments[0];
            if (in_array($method, ['GET', 'DELETE', 'HEAD', 'OPTIONS'])) {
                $headers = isset($arguments[1]) ? $arguments[1] : [];
                $rawBody = '';
            } else {
                $headers = isset($arguments[2]) ? $arguments[2] : [];
                $rawBody = '';
                if (!empty($arguments[1])) {
                    $rawBody = http_build_query($arguments[1]);
                    $headers = array_merge(['Content-Type' => 'application/x-www-form-urlencoded'], $headers);
                }
            }
        }
        return $this->request($method, $path, $rawBody, $headers);
    }

    private function validateArgument(string $funName, array $args, array $conditions)
    {
        foreach ($conditions as $key => $condition) {
            if (strpos($condition, '*') === 0) {
                $condition = substr($condition, 1);
                if (!isset($args[$key])) {
                    throw new InvalidArgumentException(
                        "Argument #" . ($key + 1) . " of " . __CLASS__ . "::$funName() is missing"
                    );
                }
            } elseif (!isset($args[$key])) {
                continue;
            }
            if (!call_user_func('is_' . strtolower($condition), $args[$key])) {
                throw new InvalidArgumentException(
                    "Argument #" . ($key + 1) . " of " . __CLASS__ . "::$funName() it is be a {$condition}, "
                    . (gettype($args[$key])) . " given"
                );
            }
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $rawBody
     * @param array $headers
     * @return TestResponse
     */
    public function request(string $method, string $path, string $rawBody = '', array $headers = []): TestResponse
    {
        $environment = $this->createEnvironment($method, $path, (bool)$this->https, $headers);
        $request = Request::createFromEnvironment($environment);
        if ('' !== $rawBody) {
            $requestBody = new RequestBody();
            $requestBody->write($rawBody);
            $request = $request->withBody($requestBody);
        }

        return $this->runApp($environment, $request);
    }

    /**
     * @param RequestInterface $request
     * @return TestResponse
     */
    public function sendRequest(RequestInterface $request): TestResponse
    {
        $headers = new Headers($request->getHeaders());
        $cookieHeaders = $request->getHeader('Cookie');
        $cookieParams = [];
        foreach ($cookieHeaders as $cookieHeader) {
            $cookiePairs = preg_split('|\s*;\s*|', $cookieHeader, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($cookiePairs as $cookiePair) {
                $cookiePair = explode("=", $cookiePair);
                $cookieParams[$cookiePair[0]] = urldecode($cookiePair[1]??'');
            }
        }
        $requestPath = $request->getUri()->getPath();
        $requestQuery = $request->getUri()->getQuery();
        $environment = $this->createEnvironment(
            $request->getMethod(),
            $requestPath . (empty($requestQuery) ? '' : '?' . $requestQuery),
            ($request->getUri()->getScheme() == 'https'),
            $request->getHeaders()
        );
        $files = [];
        if ($request instanceof ServerRequestInterface) {
            $files = $request->getUploadedFiles();
        }
        $request = new Request(
            $request->getMethod(),
            $request->getUri(),
            $headers,
            $cookieParams,
            $environment->all(),
            $request->getBody(),
            $files
        );
        return $this->runApp($environment, $request);
    }

    /**
     * @param Environment $environment
     * @param ServerRequestInterface $request
     * @return TestResponse
     */
    public function runApp(Environment $environment, ServerRequestInterface $request): TestResponse
    {
        $app = $this->getApp();
        $app->getContainer()->instance('environment', $environment);
        $app->getContainer()->instance('request', $request);
        $app->getContainer()->instance('response', new TestResponse());
        try {
            ob_start();
            $response = TestResponse::buildFromSlimResponse($app->run(true));
        } finally {
            ob_get_clean();
        }
        return $response;
    }

    protected function createEnvironment(string $method, string $requestUri, bool $https, array $httpHeaders = [])
    {
        $fakeServerVar = array_merge([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $requestUri,
            'REQUEST_SCHEME' => $https ? 'https' : 'http',
            'HTTPS' => $https ? 'on' : 'off',
            'HTTP_HOST' => 'localhost',
        ], $this->env);
        return Environment::mock(array_merge($fakeServerVar, $this->convertHttpHeader($httpHeaders)));
    }

    /**
     * @param array $httpHeaders
     * @return array
     */
    protected function convertHttpHeader(array $httpHeaders): array
    {
        $headers = [];
        foreach ($httpHeaders as $key => $val) {
            $headers['HTTP_' . strtoupper(preg_replace("/-/", '_', $key))] = $val;
        }
        return $headers;
    }
}
