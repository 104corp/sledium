<?php

namespace Sledium\Exceptions;

/**
 * Class HttpBadRequestException
 * @package Sledium\Exceptions
 */
class HttpBadRequestException extends HttpClientException
{
    /**
     * HttpBadRequestException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(400, 'Bad Request', $message, $headers, $previous);
    }
}
