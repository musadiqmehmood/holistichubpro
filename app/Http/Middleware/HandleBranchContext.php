<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleBranchContext
{
    /**
     * Handle an incoming request.
     *
     * This middleware detects the branch context from the 'X-Branch-ID' header
     * and ensures that the application logic respects this branch.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branchId = $request->header('X-Branch-ID');

        if ($branchId) {
            // Store the branch ID in the request or a global singleton for easy access
            $request->merge(['context_branch_id' => $branchId]);
        }

        return $next($request);
    }
}
