<?php


namespace Sledium\Handlers;

use Exception;
use Sledium\Exceptions\HttpException;
use Sledium\Interfaces\ErrorRendererInterface;
use Sledium\Interfaces\ErrorReporterInterface;
use Illuminate\Contracts\Debug\ExceptionHandler as IlluminateExceptionHandlerInterface;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;

/**
 * Laravel ExceptionHandler alternative class
 * Class IlluminateExceptionHandler
 * @package Sledium\Handlers
 */
class IlluminateExceptionHandler implements IlluminateExceptionHandlerInterface
{
    use DetermineContentTypeAbleTrait;

    /** @var ErrorRendererInterface */
    private $renderer;

    /** @var ErrorReporterInterface */
    private $reporter;

    public function __construct(ErrorRendererInterface $renderer, ErrorReporterInterface $reporter)
    {
        $this->reporter = $reporter;
        $this->renderer = $renderer;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        $this->reporter->report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        $accept = $request->headers->get('Accept', '');
        if (is_array($accept)) {
            $accept = implode(',', $accept);
        }
        $contentType = $this->determineContentType($accept, $this->renderer->getKnownContentTypes());
        $renderedBody = $this->renderer->render($e, true, $contentType);
        $status = 500;
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
        }
        $header = [
            'Content-Type' => $contentType,
        ];
        return \Symfony\Component\HttpFoundation\Response::create($renderedBody, $status, $header);
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
}
