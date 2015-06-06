<?php

namespace Vanchelo\LaravelConsole\Http\Middlewares;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\Middleware;

class Access implements Middleware
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Guard
     */
    private $auth;

    /**
     * @param Config $config
     */
    public function __construct(Config $config, Guard $auth)
    {
        $this->config = $config;
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->auth->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest('auth/login');
            }
        }

        return $next($request);
    }
}
