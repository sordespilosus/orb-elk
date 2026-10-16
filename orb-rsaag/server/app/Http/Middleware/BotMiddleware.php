<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BotMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('api_token')) {
            if ($request->header('api_token') === '5c1dd2ba-3c41-43ec-aaf8-ebb5852a79d8') {
                return $next($request);
            }
        }
        return response()->json(['error' => 'Unauthorized', 401]);
    }
}
