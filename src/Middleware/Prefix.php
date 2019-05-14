<?php

namespace Ichynul\LaADuo\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ichynul\LaADuo\LaADuoExt;

class Prefix
{
    public function handle(Request $request, Closure $next, $prefix)
    {
        LaADuoExt::$bootPrefix = $prefix;

        LaADuoExt::overrideConfig($prefix);

        return $next($request);
    }
}
