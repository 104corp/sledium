<?php

namespace Sledium\Exceptions;

/**
 * Class HttpPreconditionRequiredException
 * @package Sledium\Exceptions
 */
class HttpPreconditionRequiredException extends HttpClientException
{
    /**
     * HttpPreconditionRequiredException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(428, 'Precondition Required', $message, $headers, $previous);
    }
}
