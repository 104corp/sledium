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
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(403, 'Forbidden', $message, $headers);
    }
}
