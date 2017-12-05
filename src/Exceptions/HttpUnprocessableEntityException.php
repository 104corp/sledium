<?php

namespace Sledium\Exceptions;

/**
 * Class HttpUnprocessableEntityException
 * @package Sledium\Exceptions
 */
class HttpUnprocessableEntityException extends HttpClientException
{
    /**
     * HttpUnprocessableEntityException constructor.
     * @param string $message
     * @param array $headers
     */
    public function __construct($message = "", array $headers = [])
    {
        parent::__construct(422, 'Unprocessable Entity', $message, $headers);
    }
}
