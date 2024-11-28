<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if (!$request->user() || !$request->user()->hasRole($ability)) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions.'
            ], 403);
        }

        return $next($request);
    }
}
