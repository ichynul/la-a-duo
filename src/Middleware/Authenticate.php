<?php

namespace Ichynul\LaADuo\Middleware;

use Encore\Admin\Middleware\Authenticate as BaseAuthenticate;
use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Support\Facades\Auth;

class Authenticate extends BaseAuthenticate
{
    public function handle($request, \Closure $next)
    {
        $redirectTo = admin_base_path(config('admin.auth.redirect_to', 'auth/login'));

        $guard = LaADuoExt::guard();

        if ($guard->guest() && !$this->shouldPassThrough($request)) {

            return redirect()->guest($redirectTo);
        }

        if (!$guard->guest()) {

            Auth::guard('admin')->setUser($guard->user());// in old version of laravel-admin, this is needed .
        }

        return $next($request);
    }
}
