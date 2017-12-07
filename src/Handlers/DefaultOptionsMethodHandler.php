<?php


namespace Sledium\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Body;

class DefaultOptionsMethodHandler
{
    use DetermineContentTypeAbleTrait;

    protected $knownContentTypes = [
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ): ResponseInterface {
        if (!in_array('OPTIONS', $allowedMethods)) {
            $allowedMethods[] = 'OPTIONS';
        }
        $contentType = $this->determineContentType(
            (string)$request->getHeaderLine('Accept'),
            $this->knownContentTypes
        );

        switch ($contentType) {
            case 'application/json':
                $output = json_encode(['allowed' => $allowedMethods], JSON_PRETTY_PRINT);
                break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXml($allowedMethods);
                break;

            default:
                $contentType = 'text/html';
                $output = $this->renderHtml($allowedMethods);
                break;
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);
        $allow = implode(', ', $allowedMethods);
        return $response
            ->withStatus(200)
            ->withHeader('Content-type', $contentType)
            ->withHeader('Allow', $allow)
            ->withBody($body);
    }

    private function renderXml(array $allowedMethods): string
    {
        $output = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"
            . "<allowed>\n";
        foreach ($allowedMethods as $allowedMethod) {
            $output .= "    <method>{$allowedMethod}</method>\n";
        }
        $output .= "</allowed>\n";
        return $output;
    }

    private function renderHtml(array $allowedMethods): string
    {
        return sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
            "display:inline-block;width:65px;}</style></head><body><h1>%s</h1></body></html>",
            "Allowed methods",
            "Allowed methods: " . implode(", ", $allowedMethods)
        );
    }
}
