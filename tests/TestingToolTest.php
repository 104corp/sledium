<?php


namespace Sledium\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sledium\App;
use Sledium\Testing\TestCase;
use Sledium\Testing\TestClient;

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
            ['GET', 'DELETE', 'OPTIONS', 'HEAD'],
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


    protected function createHttpApp(): App
    {
        return include $this->getBasePath() . '/bootstrap/http.php';
    }

    protected function getBasePath()
    {
        return __DIR__ . '/Fixtures/resources/TestingToolTest';
    }
}
