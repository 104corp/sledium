<?php


namespace Sledium\Testing;

use Slim\Http\Response as SlimResponse;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

class TestResponse extends SlimResponse
{
    public static function buildFromSlimResponse(ResponseInterface $psrResponse): TestResponse
    {
        if ($psrResponse instanceof TestResponse) {
            return $psrResponse;
        }
        $response = new self();
        $response->status = $psrResponse->getStatusCode();
        $response->protocolVersion = $psrResponse->getProtocolVersion();
        $response->headers = new \Slim\Http\Headers($psrResponse->getHeaders());
        $response->body = $psrResponse->getBody();
        $response->reasonPhrase = $psrResponse->getReasonPhrase();
        return $response;
    }

    /**
     * get response body
     * @return null|string
     */
    public function getBodyContent()
    {
        if ($this->getBody()->isSeekable()) {
            $this->getBody()->rewind();
        }
        $content = $this->getBody()->getContents();
        return $content;
    }

    /**
     * @param string $expectedContainStr
     * @return $this
     */
    public function assertSee(string $expectedContainStr)
    {
        if (!empty($expectedContainStr)) {
            $body = $this->getBodyContent();
            Assert::assertRegExp("/" . addslashes($expectedContainStr) . "/", $body);
        }
        return $this;
    }

    /**
     * @param string $expectedBody
     * @return $this
     */
    public function assertBody(string $expectedBody)
    {
        $body = $this->getBodyContent();
        Assert::assertEquals($expectedBody, $body);
        return $this;
    }


    /**
     * assert response status code
     * @param int $expectedCode
     * @return static
     */
    public function assertStatus(int $expectedCode)
    {
        Assert::assertEquals(
            $expectedCode,
            $this->getStatusCode(),
            "Expected Status is '$expectedCode', but '" . $this->getStatusCode() . "' got"
        );
        return $this;
    }

    /**
     * assert response json
     * @param array $expected
     * @return static
     */
    public function assertJson(array $expected)
    {
        $body = $this->getBodyContent();
        $expectJsonString = json_encode($expected);
        Assert::assertJsonStringEqualsJsonString($expectJsonString, $body);
        return $this;
    }
}
