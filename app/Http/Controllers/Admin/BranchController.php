<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Branch::class);

        $perPage = $request->input('per_page', 15);

        $branches = Branch::query()
            ->when($request->boolean('active_only'), fn($q) => $q->active())
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Branch::class);

        $validated = $request->validate($this->validationRules());

        $branch = Branch::create($validated);

        AuditLogService::log(
            'created',
            Branch::class,
            $branch->id,
            null,
            $branch->toArray()
        );

        return response()->json($branch, 201);
    }

    public function show(Branch $branch)
    {
        $this->authorize('view', $branch);
        return response()->json($branch);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorize('update', $branch);

        $oldValues = $branch->toArray();

        $validated = $request->validate($this->validationRules(true));

        $branch->update($validated);

        AuditLogService::log(
            'updated',
            Branch::class,
            $branch->id,
            $oldValues,
            $branch->fresh()->toArray()
        );

        return response()->json($branch);
    }

    public function destroy(Request $request, Branch $branch)
    {
        $this->authorize('delete', $branch);

        // Check if branch has users
        if ($branch->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete branch with assigned users.'
            ], 409);
        }

        // Check if branch is used in store settings
        if (\App\Models\StoreSetting::where('branch_id', $branch->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete branch because it is used in store settings.'
            ], 409);
        }

        $oldValues = $branch->toArray();
        $branchId = $branch->id;

        AuditLogService::log('deleted', Branch::class, $branchId, $oldValues, null);
        $branch->delete();

        return response()->json(['message' => 'Branch deleted successfully']);
    }

    private function validationRules(bool $isUpdate = false): array
    {
        return [
            'name'           => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:255',
            'address'        => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'zip_code'       => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',
            'is_active'      => ($isUpdate ? 'sometimes|' : '') . 'boolean',
            'opening_time'   => 'nullable|string',
            'closing_time'   => 'nullable|string',
            'working_days'   => 'nullable|array',
            'working_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ];
    }
}
