<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\{Request, Response};
use Illuminate\Support\Facades\{Lang, Log};
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        OAuthServerException::class
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param Exception $exception
     * @return void
     * @throws Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Exception $exception
     * @return Response
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }

    /**
     * Render the given HttpException.
     *
     * @param HttpExceptionInterface $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderHttpException(HttpExceptionInterface $e)
    {
        $this->registerErrorViewPaths();

        if (view()->exists($view = "errors/error")) {

            $statusCode = $this->isHttpException($e) ? $e->getStatusCode() : 500;

            $messages = Lang::get('errors.' . $statusCode);
            if ($messages == 'errors.' . $statusCode) {
                $messages = ['code' => $statusCode, 'title' => '', 'message' => $e->getMessage()];
            }
            if ($e->getMessage()) {
                Log::error($e->getMessage());
            }
            return response()->view($view, [
                'errors' => [$messages],
                'exception' => $e,
            ], $e->getStatusCode(), $e->getHeaders());
        }

        return $this->convertExceptionToResponse($e);
    }
}
