<?php

namespace Sledium\Exceptions;

/**
 * Class HttpNotFoundException
 * @package Sledium\Exceptions
 */
class HttpNotFoundException extends HttpClientException
{
    /**
     * HttpNotFoundException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(404, 'Not Found', $message, $headers, $previous);
    }
}
