<?php

namespace Sledium\Exceptions;

/**
 * Class HttpNotAcceptableException
 * @package Sledium\Exceptions
 */
class HttpNotAcceptableException extends HttpClientException
{
    /**
     * HttpNotAcceptableException constructor.
     * @param string $message
     * @param array $headers
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(406, 'Not Acceptable', $message, $headers);
    }
}
