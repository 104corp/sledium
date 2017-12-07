<?php

namespace Sledium\Exceptions;

/**
 * Class HttpTooManyRequestsException
 * @package Sledium\Exceptions
 */
class HttpTooManyRequestsException extends HttpClientException
{
    /**
     * HttpTooManyRequestsException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(429, 'Too Many Requests', $message, $headers, $previous);
    }
}
