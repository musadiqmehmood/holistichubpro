<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxGroupController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/admin/tax-groups
     *
     * Each group includes `taxes_detail` — the full Tax objects for every id in
     * `tax_ids`, so the frontend never needs a second round-trip.
     * Also returns `available_taxes` so the create/edit form can render the
     * multi-select from DB data without a separate request.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxGroup::class);

        $query = TaxGroup::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

        $paginator = $query->paginate((int) ($request->per_page ?? 15));

        $paginator->through(
            fn(TaxGroup $g) => array_merge($g->toArray(), ['taxes_detail' => $g->taxes()])
        );

        return $this->successWith($paginator->items(), [
            'meta'            => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'available_taxes' => Tax::active()->orderBy('name')->get(['id', 'name', 'percentage']),
        ]);
    }

    /**
     * POST /api/v1/admin/tax-groups
     *
     * `calculated_percentage` is computed server-side by summing the selected
     * taxes — it is never trusted from the client.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TaxGroup::class);

        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:tax_groups,name',
            'tax_ids'   => 'required|array|min:1',
            'tax_ids.*' => 'integer|exists:taxes,id',
            'status'    => 'sometimes|boolean',
        ], [
            'tax_ids.required' => 'Please select at least one tax for this group.',
            'tax_ids.min'      => 'Please select at least one tax for this group.',
            'tax_ids.*.exists' => 'One or more selected taxes do not exist.',
        ]);

        $taxIds = $validated['tax_ids'];

        $group = TaxGroup::create([
            'name'                  => $validated['name'],
            'tax_ids'               => $taxIds,
            'calculated_percentage' => Tax::whereIn('id', $taxIds)->sum('percentage'),
            'status'                => $validated['status'] ?? true,
            'created_by'            => auth()->id(),
        ]);

        AuditLogService::log('created', TaxGroup::class, $group->id, null, $group->toArray());

        return $this->created(
            array_merge($group->toArray(), ['taxes_detail' => $group->taxes()]),
            'Tax group created successfully'
        );
    }

    /**
     * GET /api/v1/admin/tax-groups/{taxGroup}
     */
    public function show(TaxGroup $taxGroup): JsonResponse
    {
        $this->authorize('view', $taxGroup);

        return $this->successWith(
            array_merge($taxGroup->toArray(), ['taxes_detail' => $taxGroup->taxes()]),
            ['available_taxes' => Tax::active()->orderBy('name')->get(['id', 'name', 'percentage'])]
        );
    }

    /**
     * PUT /api/v1/admin/tax-groups/{taxGroup}
     *
     * Recalculates `calculated_percentage` whenever `tax_ids` changes.
     */
    public function update(Request $request, TaxGroup $taxGroup): JsonResponse
    {
        $this->authorize('update', $taxGroup);

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255|unique:tax_groups,name,' . $taxGroup->id,
            'tax_ids'   => 'sometimes|array|min:1',
            'tax_ids.*' => 'integer|exists:taxes,id',
            'status'    => 'sometimes|boolean',
        ], [
            'tax_ids.min'      => 'Please select at least one tax for this group.',
            'tax_ids.*.exists' => 'One or more selected taxes do not exist.',
        ]);

        $old = $taxGroup->toArray();

        if (isset($validated['tax_ids'])) {
            $validated['calculated_percentage'] = Tax::whereIn('id', $validated['tax_ids'])->sum('percentage');
        }

        $taxGroup->update($validated);

        AuditLogService::log('updated', TaxGroup::class, $taxGroup->id, $old, $taxGroup->fresh()->toArray());

        $fresh = $taxGroup->fresh();

        return $this->success(
            array_merge($fresh->toArray(), ['taxes_detail' => $fresh->taxes()]),
            'Tax group updated successfully'
        );
    }

    /**
     * DELETE /api/v1/admin/tax-groups/{taxGroup}
     */
    public function destroy(TaxGroup $taxGroup): JsonResponse
    {
        $this->authorize('delete', $taxGroup);

        $old = $taxGroup->toArray();
        $id  = $taxGroup->id;
        $taxGroup->delete();

        AuditLogService::log('deleted', TaxGroup::class, $id, $old, null);

        return $this->deleted('Tax group deleted successfully');
    }
}
