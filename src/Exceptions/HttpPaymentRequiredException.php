<?php

namespace Sledium\Exceptions;

/**
 * Class HttpPaymentRequiredException
 * @package Sledium\Exceptions
 */
class HttpPaymentRequiredException extends HttpClientException
{
    /**
     * HttpPaymentRequiredException constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", array $headers = [], \Throwable $previous = null)
    {
        parent::__construct(402, 'Payment Required', $message, $headers, $previous);
    }
}
