<?php


namespace Sledium\Exceptions;

class HttpClientException extends HttpException
{
    /**
     * HttpClientException constructor.
     * @param int $statusCode
     * @param string $statusReasonPhrase
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct(
        int $statusCode,
        string $statusReasonPhrase,
        $message = "",
        array $headers = [],
        \Throwable $previous = null
    ) {
        if ($statusCode < 400 || $statusCode > 499) {
            throw new \InvalidArgumentException("Invalid HTTP client error status code '$statusCode'");
        }
        parent::__construct($statusCode, $statusReasonPhrase, $message, $headers, $previous);
    }
}
