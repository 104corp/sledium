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
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(405, 'Method Not Allowed', $message, $headers);
    }
}
