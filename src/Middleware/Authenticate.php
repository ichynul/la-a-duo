<?php

namespace Ichynul\LaADuo\Middleware;

use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Support\Facades\Auth;
use Encore\Admin\Middleware\Authenticate as BaseAuthenticate;

class Authenticate extends BaseAuthenticate
{
    public function handle($request, \Closure $next)
    {
        $redirectTo = admin_base_path(config('admin.auth.redirect_to', 'auth/login'));

        $prefix = LaADuoExt::$bootPrefix;

        if (Auth::guard($prefix)->guest() && !$this->shouldPassThrough($request)) {
            return redirect()->guest($redirectTo);
        }

        if (!Auth::guard($prefix)->guest()) {
            Auth::guard('admin')->setUser(Auth::guard($prefix)->user());
        }
        
        return $next($request);
    }
}
