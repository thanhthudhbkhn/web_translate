<?php

namespace App\Http\Middleware;

use Closure;
use Sentinel;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 1. user should be authenticated 
        // 2. authenticated user should be an admin
        if (Sentinel::check() && Sentinel::inRole('user')) {
            return $next($request);    
        }
        return redirect('home');
    }
}
