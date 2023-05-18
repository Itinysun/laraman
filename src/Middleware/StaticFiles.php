<?php

namespace Itinysun\Laraman\Middleware;



use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StaticFiles
{
    public function handle(Request $request, Closure $next)
    {
        // Access to files beginning with. Is prohibited
        if (str_contains($request->path(), '/.')) {
            return response('<h1>403 forbidden</h1>', 403);
        }
        return $next($request);
    }
}
