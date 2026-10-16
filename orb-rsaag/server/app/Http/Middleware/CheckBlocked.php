<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\{Auth, DB};

class CheckBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $field = DB::connection('editor')->table('oauth.users')->select('blocked', 'is_deleted')->where('id', Auth::id())->first();
            if ($field->blocked || $field->is_deleted) {
                Auth::logout();
                return redirect()->route('home');
            }
        }
        return $next($request);
    }
}
