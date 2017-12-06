<?php


namespace Sledium\Interfaces;

interface ErrorRendererInterface
{
    /**
     * It can return string or simply echo rendered content
     * @param \Throwable $e
     * @param bool $displayErrorDetails
     * @param string $contentType
     * @return string|null
     */
    public function render(
        \Throwable $e,
        bool $displayErrorDetails,
        string &$contentType
    );

    /**
     * Known handled content types,
     * use to determine which content type we know about is wanted using Accept header
     * @return string[]
     */
    public function getKnownContentTypes(): array;
}
