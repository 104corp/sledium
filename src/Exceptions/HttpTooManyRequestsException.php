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
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(429, 'Too Many Requests', $message, $headers);
    }
}
