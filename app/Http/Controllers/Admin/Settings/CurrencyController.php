<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CurrencyController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Currency::class);

        $query = Currency::query()
            ->when($request->filled('search'),
                fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('code', 'like', '%' . $request->search . '%');
                }))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

        return $this->paginated($query->paginate((int) ($request->per_page ?? 15)));
    }

    public function publicIndex(): JsonResponse
    {
        return $this->success(
            Cache::remember('currencies.public', 3600, fn() =>
            Currency::active()->orderBy('name')->get(['id', 'name', 'code', 'symbol'])
            )
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Currency::class);

        $request->merge(['code' => strtoupper(trim($request->input('code', '')))]);

        $validated = $request->validate([
            'name'   => 'required|string|max:255|unique:currencies,name',
            'code'   => 'required|string|size:3|unique:currencies,code',
            'symbol' => 'required|string|max:10',
            'status' => 'sometimes|boolean',
        ], [
            'code.size'   => 'Currency code must be exactly 3 characters (e.g. USD, EUR, GBP).',
            'code.unique' => 'This currency code is already registered.',
        ]);

        $currency = Currency::create(array_merge($validated, ['created_by' => auth()->id()]));

        Cache::forget('currencies.public');

        AuditLogService::log('created', Currency::class, $currency->id, null, $currency->toArray());

        return $this->created($currency);
    }

    public function show(Currency $currency): JsonResponse
    {
        $this->authorize('view', $currency);
        return $this->success($currency);
    }

    public function update(Request $request, Currency $currency): JsonResponse
    {
        $this->authorize('update', $currency);

        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255|unique:currencies,name,' . $currency->id,
            'code'   => 'sometimes|string|size:3|unique:currencies,code,' . $currency->id,
            'symbol' => 'sometimes|string|max:10',
            'status' => 'sometimes|boolean',
        ], [
            'code.size'   => 'Currency code must be exactly 3 characters (e.g. USD, EUR, GBP).',
            'code.unique' => 'This currency code is already registered.',
        ]);

        $old = $currency->toArray();
        $currency->update($validated);

        Cache::forget('currencies.public');

        AuditLogService::log('updated', Currency::class, $currency->id, $old, $currency->fresh()->toArray());

        return $this->updated($currency->fresh());
    }

    public function destroy(Currency $currency): JsonResponse
    {
        $this->authorize('delete', $currency);

        if (\App\Models\StoreSetting::where('currency', $currency->code)->exists()) {
            return $this->conflict('Cannot delete currency because it is used in store settings.');
        }

        $old = $currency->toArray();
        $id  = $currency->id;
        $currency->delete();

        Cache::forget('currencies.public');

        AuditLogService::log('deleted', Currency::class, $id, $old, null);

        return $this->deleted('Currency deleted successfully');
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Currency::class);

        $data = Currency::query()
            ->when($request->filled('search'),
                fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'),
                fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'symbol', 'status', 'created_at']);

        return $this->success($data);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Currency::class);

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

                if (empty(trim($data['name'] ?? '')) || empty(trim($data['code'] ?? ''))) {
                    $skipped++;
                    continue;
                }

                $rowValidator = validator($data, [
                    'name'   => 'required|string|max:255',
                    'code'   => 'required|string|size:3',
                    'symbol' => 'required|string|max:10',
                    'status' => 'nullable',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = 'Row ' . ($i + 2) . ': ' . implode(', ', $rowValidator->errors()->all());
                    $skipped++;
                    continue;
                }

                $status = in_array(strtolower($data['status'] ?? '1'), ['1', 'true', 'active', 'yes'], true);
                $code   = strtoupper(trim($data['code']));

                $result = Currency::firstOrCreate(
                    ['code' => $code],
                    ['name' => trim($data['name']), 'symbol' => $data['symbol'], 'status' => $status, 'created_by' => auth()->id()]
                );

                $result->wasRecentlyCreated ? $imported++ : $skipped++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError('Import failed: ' . $e->getMessage());
        }

        if ($imported > 0) {
            AuditLogService::log('bulk_imported', Currency::class, 0, null, [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => count($errors),
            ]);
        }

        return $this->success(compact('imported', 'skipped', 'errors'), 'Import complete');
    }
}
