<?php

namespace Ichynul\LaADuo\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ichynul\LaADuo\LaADuoExt;

class Config
{
    public function handle(Request $request, Closure $next, $index)
    {
        $prefix = LaADuoExt::getPrefix($index);

        LaADuoExt::$bootPrefix = $prefix;

        LaADuoExt::overrideConfig($prefix);

        return $next($request);
    }
}
