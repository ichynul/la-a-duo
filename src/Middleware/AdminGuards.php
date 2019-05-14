<?php

namespace Ichynul\LaADuo\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminGuards
{
    public function handle(Request $request, Closure $next)
    {
        if (!LaADuoExt::config('apart', true) || LaADuoExt::guard()->guest()) {
            return $next($request);
        }

        $prefixes = LaADuoExt::config('prefixes', []);

        $prefixes = array_diff($prefixes, [LaADuoExt::$bootPrefix]);

        array_push($prefixes, 'admin');

        foreach ($prefixes as $prefix) {

            Session::remove(Auth::guard($prefix)->getName()); // delete session state of other guards.
        }

        return $next($request);
    }
}
