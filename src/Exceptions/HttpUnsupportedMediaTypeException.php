<?php

namespace Sledium\Exceptions;

/**
 * Class HttpUnsupportedMediaTypeException
 * @package Sledium\Exceptions
 */
class HttpUnsupportedMediaTypeException extends HttpClientException
{
    /**
     * HttpUnsupportedMediaTypeException constructor.
     * @param string $message
     * @param array $headers
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(415, 'Unsupported Media Type', $message, $headers);
    }
}
