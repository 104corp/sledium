<?php


namespace Sledium\Handlers;

use Sledium\Container;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler as IlluminateExceptionHandlerInterface;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Psr\Http\Message\ServerRequestInterface;

class IlluminateExceptionHandler implements IlluminateExceptionHandlerInterface
{
    protected $container;
    protected $displayErrorDetails = false;
    protected $dontReport = [];
    protected $knownContentTypes = [
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->displayErrorDetails = $this->container->has('settings')
            ? $this->container->get('settings')->get('displayErrorDetails', [])
            : false;
    }

    /**
     * @param Exception $e
     * @throws Exception
     */
    public function report(Exception $e)
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        try {
            $logger = $this->container->make('Psr\Log\LoggerInterface');
        } catch (Exception $ex) {
            throw $e; // throw the original exception
        }

        $logger->error($e);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Exception $e
     * @return bool
     */
    protected function shouldntReport(Exception $e)
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \Illuminate\Http\Request|ServerRequestInterface $request
     * @param Exception $exception
     * @return mixed
     */
    public function render($request, Exception $exception)
    {
        $contentType = $this->determineContentType($request);
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonErrorMessage($exception);
                break;
            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXmlErrorMessage($exception);
                break;
            default:
                $output = $this->renderHtmlErrorMessage($exception);
        }
        return $output;
    }

    protected function determineContentType($request)
    {
        if ($request instanceof ServerRequestInterface) {
            $acceptHeader = $request->getHeaderLine('Accept');
            $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->knownContentTypes);
        } else {
            /** @var \Symfony\Component\HttpFoundation\Request $request */
            $acceptHeader = $request->headers->get('Accept', []);
            $selectedContentTypes = array_intersect($acceptHeader, $this->knownContentTypes);
        }

        if (count($selectedContentTypes)) {
            return current($selectedContentTypes);
        }

        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (in_array($mediaType, $this->knownContentTypes)) {
                return $mediaType;
            }
        }
        return 'text/html';
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  \Exception $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        (new SymfonyConsoleApplication)->renderException($e, $output);
    }

    /**
     * @param $exception
     * @return string
     */
    protected function renderJsonErrorMessage(Exception $exception)
    {
        $error = [
            'message' => 'Application Error',
        ];

        if ($this->displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = [
                    'type' => get_class($exception),
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => explode("\n", $exception->getTraceAsString()),
                ];
            } while ($exception = $exception->getPrevious());
        }
        return json_encode($error, JSON_PRETTY_PRINT);
    }

    protected function renderXmlErrorMessage(Exception $exception)
    {
        $xml = "<error>\n  <message>Application Error</message>\n";
        if ($this->displayErrorDetails) {
            do {
                $xml .= "  <exception>\n";
                $xml .= "    <type>" . get_class($exception) . "</type>\n";
                $xml .= "    <code>" . $exception->getCode() . "</code>\n";
                $xml .= "    <message>" . $this->createCdataSection($exception->getMessage()) . "</message>\n";
                $xml .= "    <file>" . $exception->getFile() . "</file>\n";
                $xml .= "    <line>" . $exception->getLine() . "</line>\n";
                $xml .= "    <trace>" . $this->createCdataSection($exception->getTraceAsString()) . "</trace>\n";
                $xml .= "  </exception>\n";
            } while ($exception = $exception->getPrevious());
        }
        $xml .= "</error>";
        return $xml;
    }

    protected function renderHtmlErrorMessage(Exception $exception)
    {
        $title = 'Application Error';

        if ($this->displayErrorDetails) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderHtmlException($exception);

            while ($exception = $exception->getPrevious()) {
                $html .= '<h2>Previous exception</h2>';
                $html .= $this->renderHtmlException($exception);
            }
        } else {
            $html = '<p>A website error has occurred. Sorry for the temporary inconvenience.</p>';
        }

        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
            "display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
        );

        return $output;
    }

    /**
     * @param string $content
     * @return string
     */
    private function createCdataSection(string $content)
    {
        return sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $content));
    }

    protected function renderHtmlException(\Throwable $exception)
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        if (($code = $exception->getCode())) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        if (($message = $exception->getMessage())) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        if (($file = $exception->getFile())) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        if (($line = $exception->getLine())) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        if (($trace = $exception->getTraceAsString())) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }
}
