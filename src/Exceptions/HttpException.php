<?php


namespace Sledium\Exceptions;

class HttpException extends \RuntimeException
{
    /** @var int */
    protected $statusCode;

    /** @var string */
    protected $statusReasonPhrase;

    protected $headers = [];

    /**
     * HttpException constructor.
     * @param int $statusCode
     * @param string $statusReasonPhrase
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct(
        int $statusCode,
        string $statusReasonPhrase,
        string $message = "",
        array $headers = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->statusReasonPhrase = $statusReasonPhrase;
        $this->headers = $headers;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getStatusReasonPhrase(): string
    {
        return $this->statusReasonPhrase;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
