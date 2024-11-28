<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        Log::info('Authentication attempt', [
            'token' => $request->bearerToken(),
            'headers' => $request->headers->all(),
            'is_api' => $request->is('api/*')
        ]);

        if ($request->is('api/*')) {
            return null;
        }
        
        return $request->expectsJson() ? null : route('login');
    }

    protected function unauthenticated($request, array $guards)
    {
        Log::error('Unauthenticated access', [
            'guards' => $guards,
            'token' => $request->bearerToken()
        ]);

        abort(401, 'Unauthenticated');
    }
}
