<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriver
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isDriver() && $request->user()->is_active, 403);

        return $next($request);
    }
}
