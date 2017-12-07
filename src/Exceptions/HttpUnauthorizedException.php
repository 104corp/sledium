<?php

namespace Sledium\Exceptions;

/**
 * Class HttpUnauthorizedException
 * @package Sledium\Exceptions
 */
class HttpUnauthorizedException extends HttpClientException
{
    /**
     * HttpUnauthorizedException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(401, 'Unauthorized', $message, $headers, $previous);
    }
}
