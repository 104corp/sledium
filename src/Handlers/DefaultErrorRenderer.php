<?php


namespace Sledium\Handlers;

use Sledium\Exceptions\HttpException;
use Sledium\Interfaces\ErrorRendererInterface;

/**
 * Default HTTP error renderer
 * Class DefaultErrorRenderer
 * @package Sledium\Handlers
 */
class DefaultErrorRenderer implements ErrorRendererInterface
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
    ) {
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJson($e, $displayErrorDetails);
                break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXml($e, $displayErrorDetails);
                break;

            default:
                $contentType = 'text/html';
                $output = $this->renderHtml($e, $displayErrorDetails);
                break;
        }
        return $output;
    }

    /**
     * Known handled content types,
     * Use to determine which content type we know about is wanted using Accept header
     * @return string[]
     */
    public function getKnownContentTypes(): array
    {
        return [
            'application/json',
            'application/xml',
            'text/xml',
            'text/html',
        ];
    }

    private function renderJson(\Throwable $e, bool $displayErrorDetails)
    {
        $message = $e->getMessage();
        if ($e instanceof HttpException) {
            $error = [
                'message' => empty($message) ? $e->getStatusReasonPhrase() : $message
            ];
        } else {
            $reasonPhrase = 'Internal Server Error';
            $error = [
                'message' => $displayErrorDetails
                    ? (empty($message) ? $reasonPhrase : $message)
                    : $reasonPhrase
            ];
        }
        if ($displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = [
                    'type' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
            } while ($e = $e->getPrevious());
        }

        return json_encode($error, JSON_PRETTY_PRINT);
    }

    private function renderXml(\Throwable $e, bool $displayErrorDetails)
    {
        $message = $e->getMessage();
        if ($e instanceof HttpException) {
            $message = empty($message) ? $e->getStatusReasonPhrase() : $message;
        } else {
            $reasonPhrase = 'Internal Server Error';
            $message = $displayErrorDetails
                ? (empty($message) ? $reasonPhrase : $message)
                : $reasonPhrase;
        }
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<error>\n  <message>"
            . $this->xmlEscapeString($message, ENT_XML1, 'UTF-8') . "</message>";
        if ($displayErrorDetails) {
            do {
                $xml .= "  <exception>\n";
                $xml .= "    <type>" . get_class($e) . "</type>\n";
                $xml .= "    <code>" . $e->getCode() . "</code>\n";
                $xml .= "    <message>" . $this->xmlEscapeString($e->getMessage()) . "</message>\n";
                $xml .= "    <file>" . $e->getFile() . "</file>\n";
                $xml .= "    <line>" . $e->getLine() . "</line>\n";
                $xml .= "    <trace>" . $this->xmlEscapeString($e->getTraceAsString()) . "</trace>\n";
                $xml .= "  </exception>\n";
            } while ($e = $e->getPrevious());
        }
        $xml .= "</error>";
        return $xml;
    }

    private function xmlEscapeString(string $str): string
    {
        return htmlspecialchars($str, ENT_XML1, 'UTF-8');
    }

    private function renderHtml(\Throwable $e, bool $displayErrorDetails)
    {
        $message = $e->getMessage();
        if ($e instanceof HttpException) {
            $title = $e->getStatusCode() . ' ' . $e->getStatusReasonPhrase();
            $message = '<p>' . (empty($message) ? $e->getStatusReasonPhrase() : $message) . '</p>';
            $detailHeadLine = '';
        } else {
            $reasonPhrase = 'Internal Server Error';
            $title = "500 $reasonPhrase";
            $message = $displayErrorDetails
                ? (empty($message) ? $reasonPhrase : $message)
                : '<p>A website error has occurred. Sorry for the temporary inconvenience.</p>';
            $detailHeadLine = '<p>The application could not run because of the following error:</p>';
        }
        if ($displayErrorDetails) {
            $html = $detailHeadLine;
            $html .= '<h3>Details</h3>';
            $html .= $this->renderHtmlThrowable($e);

            while ($e = $e->getPrevious()) {
                $html .= '<h3>Previous exception</h3>';
                $html .= $this->renderHtmlThrowable($e);
            }
        } else {
            $html = $message;
        }
        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
            "display:inline-block;width:65px;}</style></head><body><h2>%s</h2>%s</body></html>",
            $title,
            $message,
            $html
        );
        return $output;
    }

    protected function renderHtmlThrowable(\Throwable $exception)
    {

        $html = '<div><strong>Type:</strong> ' . get_class($exception) . '</div>';

        if (($code = $exception->getCode())) {
            $html .= '<div><strong>Code:</strong> ' . $code . '</div>';
        }

        if (($message = $exception->getMessage())) {
            $html .= '<div><strong>Message:</strong> ' . htmlentities($message) . '</div>';
        }

        if (($file = $exception->getFile())) {
            $html .= '<div><strong>File:</strong> ' . $file . '</div>';
        }

        if (($line = $exception->getLine())) {
            $html .= '<div><strong>Line:</strong> ' . $line . '</div>';
        }

        if (($trace = $exception->getTraceAsString())) {
            $html .= '<h2>Trace</h2>';
            $html .= '<pre>' . htmlentities($trace) . '</pre>';
        }
        return $html;
    }
}
