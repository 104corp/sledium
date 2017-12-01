<?php


namespace Sledium\Testing;

use InvalidArgumentException;
use Sledium\App;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\RequestBody;

/**
 * Class Client
 * @package Corp104\Apim\Test\SlimTest
 * @method TestResponse get(string $path, array $headers = [])
 * @method TestResponse delete(string $path, array $headers = [])
 * @method TestResponse head(string $path, array $headers = [])
 * @method TestResponse options(string $path, array $headers = [])
 * @method TestResponse post(string $path, array $data, array $headers = [])
 * @method TestResponse patch(string $path, array $data, array $headers = [])
 * @method TestResponse put(string $path, array $data, array $headers = [])
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

    private $obLevel = 0;

    /**
     * TestClient constructor.
     * @param App $app
     * @param bool $https
     * @param array $env
     */
    public function __construct(App $app,bool $https = false, array $env = [])
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


    public function request(string $method, string $path, string $rawBody = '', array $headers = []): TestResponse
    {
        $method = strtoupper($method);
        $query = '';
        if (!empty($params)) {
            $query = http_build_query($params);
        }
        $envHeader = [];
        foreach ($headers as $key => $val) {
            $envHeader['HTTP_' . strtoupper(preg_replace("/-/", '_', $key))] = $val;
        }
        $requestUri = ('' === $query) ? $path : $path . (preg_match("/\?.*$/", $path) ? '&' : '?') . $query;

        $env = array_merge([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $requestUri,
            'REQUEST_SCHEME' => $this->https ? 'https' : 'http',
            'HTTPS' => $this->https ? 'on' : 'off',
        ], $this->env);
        $environment = Environment::mock(array_merge($env, $envHeader));
        $request = Request::createFromEnvironment($environment);
        if ('' !== $rawBody) {
            $requestBody = new RequestBody();
            $requestBody->write($rawBody);
            $request = $request->withBody($requestBody);
        }
        $this->obLevel = ob_get_level();
        ob_start();
        $response = $this->getApp()->process($request, new TestResponse());
        $output = ob_get_clean();
        if (!empty($output) && $response->getBody()->isWritable()) {
            $setting = $this->getApp()->getContainer()->get('settings');
            if (isset($setting['outputBuffering'])) {
                if ($setting['outputBuffering'] === 'prepend') {
                    $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
                    $body->write($output . $response->getBody());
                    $response = $response->withBody($body);
                } elseif ($setting['outputBuffering'] === 'append') {
                    $response->getBody()->write($output);
                }
            }
        }
        $this->alignObLevel();
        return TestResponse::buildFromSlimResponse($response);
    }

    /**
     * fix phpunit ob_level issue when slim occurred error
     */
    private function alignObLevel()
    {
        while ($this->obLevel > ob_get_level()) {
            ob_start();
        }
        while ($this->obLevel < ob_get_level()) {
            ob_end_clean();
        }
    }

}
