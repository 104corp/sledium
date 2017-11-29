<?php


namespace Apim\Framework\Testing;

use PHPUnit\Util\InvalidArgumentHelper;
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
        $response->headers = $psrResponse->getHeaders();
        $response->body = $psrResponse->getBody();
        $response->reasonPhrase = $psrResponse->getReasonPhrase();
        return $response;
    }

    /**
     * get response body
     * @return null|string
     */
    public function getBodyContent(): ? string
    {
        if ($this->getBody()->isSeekable()) {
            $this->getBody()->rewind();
        }
        $content = $this->getBody()->getContents();
        return $content;
    }

    /**
     * assert response http status code
     * @param int $expectedCode
     */
    public function assertStatus(int $expectedCode)
    {
        Assert::assertEquals(
            $expectedCode,
            $this->getStatusCode(),
            "Expected Status is '$expectedCode', but '" . $this->getStatusCode() . "'got"
        );
    }

    /**
     * assert response json
     * @param array $expected
     */
    public function assertJson(array $expected)
    {
        $body = $this->getBodyContent();
        $expectJsonString = json_encode($expected);
        Assert::assertJsonStringEqualsJsonString($expectJsonString, $body);
    }

    /**
     * assert response json structure
     * @param array $expectedStructure
     */
    public function assertJsonStructure(array $expectedStructure)
    {
        $body = $this->getBodyContent();
        $data = json_decode($body, true);
        $jsonError = json_last_error_msg();
        Assert::assertEmpty($jsonError, $jsonError);
        Assert::assertTrue(is_array($data), "Response is not structured json, \n {$body}");
        $this->assertArrayStructureStack($expectedStructure, $data);
    }

    /**
     * assert array structure with given array
     * @param array $expectStructure
     * @param mixed $actual
     */
    public function assertArrayStructure(array $expectStructure, $actual)
    {
        Assert::assertTrue(is_array($actual), 'Actual data is not Array');
        $this->assertArrayStructureStack($expectStructure, $actual);
    }

    private function assertArrayStructureStack(array $expectStructure, $actual, $stack = '')
    {
        foreach ($expectStructure as $key => $value) {
            $currentStack = empty($stack) ? $key : $stack . '.' . $value;
            Assert::assertArrayHasKey($value, $actual, "Missing structure " . $currentStack);
            if (is_array($value)) {
                $this->assertArrayStructureStack($value, $actual[$key], empty($stack) ? $key : $stack . '.' . $key);
            } else {
                if (!is_numeric($value) && !empty($value) && is_string($value)) {
                    $this->assertType($value, $actual[$key], $currentStack);
                }
            }
        }
    }

    private function assertType(string $expectedTypeName, $actualData, $prefix)
    {
        $typeCheckFuncName = 'is_' . strtolower($expectedTypeName);
        if (!function_exists($typeCheckFuncName)) {
            throw InvalidArgumentHelper::factory(
                1,
                "unknow type '{$expectedTypeName}'"
            );
        }
        Assert::assertTrue(
            (bool)call_user_func($typeCheckFuncName, $actualData),
            $prefix . ' that is not \'' . $expectedTypeName . '\', Actually it is \'' . gettype($actualData) . '\''
        );
    }
}
