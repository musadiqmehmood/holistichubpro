<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordIsNotExpired
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->isPasswordExpired()) {
            return response()->json([
                'message' => 'Your password has expired. Please change your password.',
                'requires_password_change' => true
            ], 403);
        }

        return $next($request);
    }
}
