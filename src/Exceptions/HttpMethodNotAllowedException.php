<?php

namespace Sledium\Exceptions;

/**
 * Class HttpMethodNotAllowedException
 * @package Sledium\Exceptions
 */
class HttpMethodNotAllowedException extends HttpClientException
{
    /**
     * HttpMethodNotAllowedException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(405, 'Method Not Allowed', $message, $headers, $previous);
    }
}
