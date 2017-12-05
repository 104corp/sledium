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
     */
    public function __construct($message = "")
    {
        parent::__construct(400, 'Bad Request', $message);
    }
}
