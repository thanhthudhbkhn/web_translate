<?php

namespace App\Http\Middleware;

use Closure;
use Sentinel;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Sentinel::check() && Sentinel::inRole('admin')) {
            return $next($request);
        }
        return redirect('home');
    }
}
