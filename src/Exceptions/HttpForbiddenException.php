<?php

namespace Sledium\Exceptions;

/**
 * Class HttpForbiddenException
 * @package Sledium\Exceptions
 */
class HttpForbiddenException extends HttpClientException
{
    /**
     * HttpForbiddenException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(403, 'Forbidden', $message, $headers, $previous);
    }
}
