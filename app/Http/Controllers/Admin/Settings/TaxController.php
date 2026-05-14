<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tax::class);

        $query = Tax::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

        return $this->paginated($query->paginate((int) ($request->per_page ?? 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Tax::class);

        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:taxes,name',
            'percentage' => 'required|numeric|min:0|max:100',
            'status'     => 'sometimes|boolean',
        ], [
            'name.unique'    => 'A tax with this name already exists.',
            'percentage.min' => 'Tax percentage cannot be negative.',
            'percentage.max' => 'Tax percentage cannot exceed 100%.',
        ]);

        $tax = Tax::create(array_merge($validated, ['created_by' => auth()->id()]));

        AuditLogService::log('created', Tax::class, $tax->id, null, $tax->toArray());

        return $this->created($tax);
    }

    public function show(Tax $tax): JsonResponse
    {
        $this->authorize('view', $tax);
        return $this->success($tax);
    }

    public function update(Request $request, Tax $tax): JsonResponse
    {
        $this->authorize('update', $tax);

        $validated = $request->validate([
            'name'       => 'sometimes|string|max:255|unique:taxes,name,' . $tax->id,
            'percentage' => 'sometimes|numeric|min:0|max:100',
            'status'     => 'sometimes|boolean',
        ]);

        $old = $tax->toArray();
        $tax->update($validated);

        AuditLogService::log('updated', Tax::class, $tax->id, $old, $tax->fresh()->toArray());

        return $this->updated($tax->fresh());
    }

    public function destroy(Tax $tax): JsonResponse
    {
        $this->authorize('delete', $tax);

        $old = $tax->toArray();
        $id  = $tax->id;
        $tax->delete();

        AuditLogService::log('deleted', Tax::class, $id, $old, null);

        return $this->deleted('Tax deleted successfully');
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tax::class);

        $data = Tax::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->get(['id', 'name', 'percentage', 'status', 'created_at']);

        return $this->success($data);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Tax::class);

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
                    'name'       => 'required|string|max:255',
                    'percentage' => 'required|numeric|min:0|max:100',
                    'status'     => 'nullable',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = 'Row ' . ($i + 2) . ': ' . implode(', ', $rowValidator->errors()->all());
                    $skipped++;
                    continue;
                }

                $status = in_array(strtolower($data['status'] ?? '1'), ['1', 'true', 'active', 'yes'], true);

                $result = Tax::firstOrCreate(
                    ['name' => trim($data['name'])],
                    ['percentage' => (float) $data['percentage'], 'status' => $status, 'created_by' => auth()->id()]
                );

                $result->wasRecentlyCreated ? $imported++ : $skipped++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError('Import failed: ' . $e->getMessage());
        }

        if ($imported > 0) {
            AuditLogService::log('bulk_imported', Tax::class, 0, null, [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => count($errors),
            ]);
        }

        return $this->success(compact('imported', 'skipped', 'errors'), 'Import complete');
    }
}
