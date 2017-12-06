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
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(422, 'Unprocessable Entity', $message, $headers, $previous);
    }
}
