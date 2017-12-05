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
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(404, 'Not Found', $message, $headers);
    }
}
