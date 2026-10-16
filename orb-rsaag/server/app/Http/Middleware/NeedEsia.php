<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NeedEsia
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user || !$user->esia_user_id) {
            return redirect('/loginESIA?redirect=' . $request->fullUrl());
        }
        return $next($request);
    }
}
