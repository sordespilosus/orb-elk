<?php

namespace App\Http\Middleware;

use App\Extensions\Helpers;
use Closure;
use Illuminate\Http\{Request, Response};

class Logger
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Функция которая будет выполнена после ответа сервера клиенту
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate($request, $response)
    {
        Helpers::loggingRoute($request);
    }
}
