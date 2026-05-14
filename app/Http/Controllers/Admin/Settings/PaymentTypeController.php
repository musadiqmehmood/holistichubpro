<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentTypeController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/admin/payment-types
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentType::class);

        $query = PaymentType::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

        return $this->paginated($query->paginate((int) ($request->per_page ?? 15)));
    }

    /**
     * POST /api/v1/admin/payment-types
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', PaymentType::class);

        $validated = $request->validate([
            'name'   => 'required|string|max:255|unique:payment_types,name',
            'status' => 'sometimes|boolean',
        ]);

        $paymentType = PaymentType::create(array_merge($validated, ['created_by' => auth()->id()]));

        AuditLogService::log('created', PaymentType::class, $paymentType->id, null, $paymentType->toArray());

        return $this->created($paymentType);
    }

    /**
     * GET /api/v1/admin/payment-types/{paymentType}
     */
    public function show(PaymentType $paymentType): JsonResponse
    {
        $this->authorize('view', $paymentType);

        return $this->success($paymentType);
    }

    /**
     * PUT /api/v1/admin/payment-types/{paymentType}
     */
    public function update(Request $request, PaymentType $paymentType): JsonResponse
    {
        $this->authorize('update', $paymentType);

        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255|unique:payment_types,name,' . $paymentType->id,
            'status' => 'sometimes|boolean',
        ]);

        $old = $paymentType->toArray();
        $paymentType->update($validated);

        AuditLogService::log('updated', PaymentType::class, $paymentType->id, $old, $paymentType->fresh()->toArray());

        return $this->updated($paymentType->fresh());
    }

    /**
     * DELETE /api/v1/admin/payment-types/{paymentType}
     */
    public function destroy(PaymentType $paymentType): JsonResponse
    {
        $this->authorize('delete', $paymentType);

        $old = $paymentType->toArray();
        $id  = $paymentType->id;
        $paymentType->delete();

        AuditLogService::log('deleted', PaymentType::class, $id, $old, null);

        return $this->deleted('Payment type deleted successfully');
    }

    /**
     * GET /api/v1/admin/payment-types/export
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentType::class);

        $data = PaymentType::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'created_at']);

        return $this->success($data);
    }

    /**
     * POST /api/v1/admin/payment-types/import
     *
     * Accepts a CSV file (columns: name, status).
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', PaymentType::class);

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
                    'name'   => 'required|string|max:255',
                    'status' => 'nullable',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = 'Row ' . ($i + 2) . ': ' . implode(', ', $rowValidator->errors()->all());
                    $skipped++;
                    continue;
                }

                $status = in_array(strtolower($data['status'] ?? '1'), ['1', 'true', 'active', 'yes'], true);

                $result = PaymentType::firstOrCreate(
                    ['name' => trim($data['name'])],
                    ['status' => $status, 'created_by' => auth()->id()]
                );

                $result->wasRecentlyCreated ? $imported++ : $skipped++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError('Import failed: ' . $e->getMessage());
        }

        if ($imported > 0) {
            AuditLogService::log('bulk_imported', PaymentType::class, 0, null, [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => count($errors),
            ]);
        }

        return $this->success(compact('imported', 'skipped', 'errors'), 'Import complete');
    }
}
