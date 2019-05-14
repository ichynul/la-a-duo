<?php

namespace Ichynul\LaADuo\Middleware;

use Encore\Admin\Middleware\Authenticate as BaseAuthenticate;
use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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
        } else {
            //Session::remove(Auth::guard('admin')->getName());
        }

        return $next($request);
    }
}
