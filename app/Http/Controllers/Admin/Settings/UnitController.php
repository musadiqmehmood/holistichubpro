<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    /**
     * GET /api/admin/units
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $query = Unit::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

        return response()->json($query->paginate((int) ($request->per_page ?? 15)));
    }

    /**
     * POST /api/admin/units
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:units,name',
            'description' => 'nullable|string|max:1000',
            'status'      => 'sometimes|boolean',
        ]);

        $unit = Unit::create(array_merge($validated, ['created_by' => auth()->id()]));

        AuditLogService::log('created', Unit::class, $unit->id, null, $unit->toArray());

        return response()->json($unit, 201);
    }

    /**
     * GET /api/admin/units/{unit}
     */
    public function show(Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        return response()->json($unit);
    }

    /**
     * PUT /api/admin/units/{unit}
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255|unique:units,name,' . $unit->id,
            'description' => 'nullable|string|max:1000',
            'status'      => 'sometimes|boolean',
        ]);

        $old = $unit->toArray();
        $unit->update($validated);

        AuditLogService::log('updated', Unit::class, $unit->id, $old, $unit->fresh()->toArray());

        return response()->json($unit->fresh());
    }

    /**
     * DELETE /api/admin/units/{unit}
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        $old = $unit->toArray();
        $id  = $unit->id;
        $unit->delete();

        AuditLogService::log('deleted', Unit::class, $id, $old, null);

        return response()->json(['message' => 'Unit deleted successfully']);
    }

    /**
     * GET /api/admin/units/export
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $data = Unit::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'status', 'created_at']);

        return response()->json($data);
    }

    /**
     * POST /api/admin/units/import
     *
     * Accepts a CSV file (columns: name, description, status).
     * Skips duplicate names (firstOrCreate).
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $path   = $request->file('file')->getRealPath();
        $rows   = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_map('strtolower', array_shift($rows)));

        $imported = $skipped = 0;
        $errors   = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), '');
                }
                $data = array_combine($header, array_slice($row, 0, count($header)));

                if (empty(trim($data['name'] ?? ''))) {
                    $skipped++;
                    continue;
                }

                $rowValidator = validator($data, [
                    'name'        => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'status'      => 'nullable',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = 'Row ' . ($i + 2) . ': ' . implode(', ', $rowValidator->errors()->all());
                    $skipped++;
                    continue;
                }

                $status = in_array(strtolower($data['status'] ?? '1'), ['1', 'true', 'active', 'yes'], true);

                $result = Unit::firstOrCreate(
                    ['name' => trim($data['name'])],
                    ['description' => $data['description'] ?? null, 'status' => $status, 'created_by' => auth()->id()]
                );

                $result->wasRecentlyCreated ? $imported++ : $skipped++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }

        if ($imported > 0) {
            AuditLogService::log('bulk_imported', Unit::class, 0, null, [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => count($errors),
            ]);
        }

        return response()->json(compact('imported', 'skipped', 'errors'));
    }
}
