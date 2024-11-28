<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfToken
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',           // Bỏ qua CSRF cho tất cả routes bắt đầu bằng api/
        'admin/*',         // Bỏ qua CSRF cho tất cả routes bắt đầu bằng admin/
        'public/api/*',    // Bỏ qua CSRF cho tất cả routes bắt đầu bằng public/api/
        'public/admin/*'   // Bỏ qua CSRF cho tất cả routes bắt đầu bằng public/admin/
    ];
}
