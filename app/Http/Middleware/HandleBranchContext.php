<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleBranchContext
{
    /**
     * Detect branch context from X-Branch-ID header or fallback to user's branch.
     * Validates the branch exists and is active, then binds it to the container
     * as 'branch_context' so policies and models can access it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branch = $this->resolveBranch($request);

        if ($branch) {
            app()->instance('branch_context', $branch);
            $request->merge(['context_branch_id' => $branch->id]);
        }

        return $next($request);
    }

    private function resolveBranch(Request $request): ?Branch
    {
        // Priority 1: X-Branch-ID header (super-admin switching branches)
        $headerBranchId = $request->header('X-Branch-ID');
        if ($headerBranchId && is_numeric($headerBranchId)) {
            $branch = Branch::where('id', (int) $headerBranchId)
                ->where('is_active', true)
                ->first();
            if ($branch) {
                return $branch;
            }
        }

        // Priority 2: Authenticated user's assigned branch
        if (auth()->check() && auth()->user()->branch_id) {
            return Branch::where('id', auth()->user()->branch_id)
                ->where('is_active', true)
                ->first();
        }

        return null;
    }
}
