<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertPutFormData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $content = $request->getContent();

            // Nếu không phải JSON request
            if (!$request->isJson()) {
                // Merge tất cả input vào request
                $request->merge($request->all());

                // Xử lý files nếu có
                if ($request->allFiles()) {
                    $request->merge(['_files' => $request->allFiles()]);
                }
            }
        }

        return $next($request);
    }
}
