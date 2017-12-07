<?php


namespace Sledium\Tests;

use function GuzzleHttp\Psr7\build_query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sledium\App;
use Sledium\Testing\TestCase;
use Sledium\Testing\TestClient;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Slim\Http\Response;

class TestingToolTest extends TestCase
{

    /**
     * @test
     */
    public function methodGetClientShouldOK()
    {
        $client = $this->getClient();
        $this->assertInstanceOf(TestClient::class, $client);
    }

    /**
     * @test
     */
    public function clientAllRequestMethodAndResponseShouldOK()
    {
        $client = $this->getClient();
        $app = $client->getApp();
        $app->map(
            ['GET', 'DELETE', 'HEAD', 'OPTIONS'],
            '/',
            function (ServerRequestInterface $request, ResponseInterface $response) {
                echo $request->getMethod();
            }
        );
        $app->map(
            ['POST', 'PATCH', 'PUT'],
            '/',
            function (ServerRequestInterface $request, ResponseInterface $response) {
                return $response->withBody($request->getBody());
            }
        );

        $client->get('/')
            ->assertStatus(200)
            ->assertSee('GET');

        $client->delete('/')
            ->assertStatus(200)
            ->assertSee('DELETE');

        $client->options('/')
            ->assertStatus(200)
            ->assertSee('OPTIONS');

        $client->head('/')
            ->assertStatus(200);

        $expect = ['abc' => '123'];
        $client->put('/', $expect)
            ->assertStatus(200)
            ->assertBody(http_build_query($expect));

        $client->postJson('/', $expect)
            ->assertStatus(200)
            ->assertJson($expect);

        $client->patchRaw('/', json_encode($expect))
            ->assertStatus(200)
            ->assertJson($expect);
    }

    /**
     * @test
     */
    public function psr7RequestShouldWork()
    {
        $client = $this->getClient();
        $app = $client->getApp();
        $app->map(
            ['GET'],
            '/foo/bar',
            function (ServerRequestInterface $request, ResponseInterface $response) {
                echo $request->getMethod();
            }
        );
        $app->map(
            ['PATCH'],
            '/foo/bar',
            function (ServerRequestInterface $request, ResponseInterface $response) {
                return (new Response())->withBody($request->getBody());
            }
        );
        $cookie = '_gaexp=GAX1.3.vCo_medgQLiOEryyaNMYbw.17565.0; '
            . 'LOGIN_LEVEL=2097219; '
            . 'PROTOCOL104=https; '
            . 'FN_CK=%25E6%259C%25B1%25E5%25BF%2597%25E6%2598%258E; '
            . 'CS=EF8776E23538C536178611D33CDCB9E8; '
            . '_ga=GA1.3.1546652316.1512673563; '
            . '_gid=GA1.3.2045537541.1512673563; '
            . 'lup=670522828.4507568175053.4507568175053.1.4640712161167';
        $headers = [
            'User-Agent' => 'Testing Agent',
            'Pragma' => 'no-cache',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Cookie' => $cookie
        ];
        $params = ['abc'=>'123', 'cde' => 'bbb'];
        $request = new PsrRequest('GET', 'https://localhost/foo/bar?'.build_query($params), $headers);
        $response = $client->sendRequest($request);
        $response->assertStatus(200);

        $request = new PsrRequest('PATCH', 'https://localhost/foo/bar?'.build_query($params), $headers);
        $response = $client->sendRequest($request);
        $response->assertStatus(200);
    }


    protected function createHttpApp(): App
    {
        return include $this->getBasePath() . '/bootstrap/http.php';
    }

    protected function getBasePath()
    {
        return __DIR__ . '/Fixtures/resources/TestingToolTest';
    }
}
