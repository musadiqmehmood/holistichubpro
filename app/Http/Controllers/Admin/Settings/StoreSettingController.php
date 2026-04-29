<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\StoreSetting;
use App\Services\AuditLogService;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingController extends Controller
{
    /**
     * GET /api/admin/store-settings
     *
     * UPDATED: Added branch_id support to make settings dynamic per branch.
     */
    public function show(Request $request): JsonResponse
    {
        // CHANGE: Detect branch from query or user context instead of just taking the first row
        $branchId = $request->query('branch_id') ?? auth()->user()->branch_id ?? Branch::first()?->id;

        if (!$branchId) {
            return response()->json(['message' => 'No branch found. Please create a branch first.'], 404);
        }

        // CHANGE: Fetch settings specific to the branch
        $setting = StoreSetting::where('branch_id', $branchId)->first();

        if (!$setting) {
            $setting = new StoreSetting(['branch_id' => $branchId]);
        }

        return response()->json([
            'data'    => $setting->load('branch'),
            'options' => $this->buildOptions(),
        ]);
    }

    /**
     * POST /api/admin/store-settings
     *
     * UPDATED: Refactored to update or create settings based on branch_id.
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage', StoreSetting::class);

        $validated = $request->validate([
            'branch_id'                 => 'required|exists:branches,id', // CHANGE: branch_id is now required
            'store_code'                => 'required|string|max:255',
            'store_name'                => 'required|string|max:255',
            'mobile'                    => 'required|string|max:20',
            'email'                     => 'required|email|max:255',
            'phone'                     => 'nullable|string|max:20',
            'gst_number'                => 'nullable|string|max:50',
            'tax_number'                => 'nullable|string|max:50',
            'pan_number'                => 'nullable|string|max:50',
            'store_website'             => 'nullable|url|max:255',
            'show_signature_on_invoice' => 'sometimes|boolean',
            'bank_details'              => 'nullable|string',
            'timezone'                  => 'sometimes|string|timezone',
            'date_format'               => 'sometimes|string|in:Y-m-d,d/m/Y,m/d/Y,d-M-Y,d.m.Y,M d\, Y',
            'time_format'               => 'sometimes|string|in:H:i,H:i:s,h:i A',
            'currency'                  => 'sometimes|string|size:3|exists:currencies,code',
            'currency_symbol_placement' => 'sometimes|in:before,after',
            'decimals'                  => 'sometimes|integer|min:0|max:4',
            'decimals_for_quantity'     => 'sometimes|integer|min:0|max:4',
            'store_logo'                => 'nullable|image|mimes:jpg,jpeg,png,webp,svg,gif,bmp|max:20480',
            'signature'                 => 'nullable|image|mimes:jpg,jpeg,png,webp,svg,gif,bmp|max:20480',
        ]);

        // CHANGE: Find settings by branch_id instead of first()
        $setting = StoreSetting::where('branch_id', $validated['branch_id'])->first();
        $isNew = false;

        if (!$setting) {
            $setting = new StoreSetting();
            $setting->branch_id = $validated['branch_id'];
            $isNew = true;
        }

        $old = $setting->exists ? $setting->toArray() : null;

        // CHANGE: Improved logo handling with existence check
        if ($request->hasFile('store_logo')) {
            if ($setting->store_logo && Storage::disk('public')->exists($setting->store_logo)) {
                Storage::disk('public')->delete($setting->store_logo);
            }
            $validated['store_logo'] = $request->file('store_logo')->store('settings/store', 'public');
        }

        if ($request->hasFile('signature')) {
            if ($setting->signature && Storage::disk('public')->exists($setting->signature)) {
                Storage::disk('public')->delete($setting->signature);
            }
            $validated['signature'] = $request->file('signature')->store('settings/store', 'public');
        }

        if (isset($validated['currency'])) {
            $validated['currency'] = strtoupper($validated['currency']);
        }

        $setting->fill($validated);
        $setting->save();

        AuditLogService::log(
            $isNew ? 'created' : 'updated',
            StoreSetting::class,
            $setting->id,
            $old,
            $setting->fresh()->toArray()
        );

        return response()->json([
            'data'    => $setting->fresh()->load('branch'),
            'options' => $this->buildOptions(),
        ]);
    }

    private function buildOptions(): array
    {
        return [
            'currencies' => Currency::active()->orderBy('name')->get(['id', 'name', 'code', 'symbol']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'timezones' => DateTimeZone::listIdentifiers(),
            'date_formats' => [
                ['value' => 'Y-m-d',   'label' => 'YYYY-MM-DD (2025-01-31)'],
                ['value' => 'd/m/Y',   'label' => 'DD/MM/YYYY (31/01/2025)'],
                ['value' => 'm/d/Y',   'label' => 'MM/DD/YYYY (01/31/2025)'],
                ['value' => 'd-M-Y',   'label' => 'DD-Mon-YYYY (31-Jan-2025)'],
                ['value' => 'd.m.Y',   'label' => 'DD.MM.YYYY (31.01.2025)'],
                ['value' => 'M d, Y',  'label' => 'Mon DD, YYYY (Jan 31, 2025)'],
            ],
            'time_formats' => [
                ['value' => 'H:i',   'label' => '24-hour (13:05)'],
                ['value' => 'H:i:s', 'label' => '24-hour with seconds (13:05:09)'],
                ['value' => 'h:i A', 'label' => '12-hour (01:05 PM)'],
            ],
            'currency_placements' => [
                ['value' => 'before', 'label' => 'Before amount ($100)'],
                ['value' => 'after',  'label' => 'After amount (100$)'],
            ],
        ];
    }
}
