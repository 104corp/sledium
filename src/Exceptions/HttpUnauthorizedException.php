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
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(401, 'Unauthorized', $message, $headers);
    }
}
