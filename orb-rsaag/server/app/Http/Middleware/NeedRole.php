<?php

namespace App\Http\Middleware;

use App\Extensions\Helpers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NeedRole
{
    /**
     * Handle an incoming request (Проверка наличия роли у пользователя).
     *
     * @param Request $request
     * @param Closure $next
     * @param string $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return redirect()->route('home');
        }
        $user = Auth::user();

        $res = Helpers::checkRoleUser($user->getAttribute('id'), $role);

        if (!$res) {
            return redirect()->route('home');
        }
        return $next($request);
    }
}
