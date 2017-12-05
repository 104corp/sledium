<?php


namespace Sledium\Exceptions;

class HttpException extends \RuntimeException
{
    /** @var int */
    protected $statusCode;

    /** @var string */
    protected $statusReasonPhrase;

    protected $headers = [];

    public function __construct(int $statusCode, string $statusReasonPhrase, string $message = "", array $headers = [])
    {
        if (empty($message)) {
            $message = "$statusCode $statusReasonPhrase";
        }
        parent::__construct($message);
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
