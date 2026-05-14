<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StoreSetting;
use App\Services\AuditLogService;
use App\Services\Settings\StoreSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreSettingController extends Controller
{
    public function show(Request $request, StoreSettingsService $service): JsonResponse
    {
        $branchId = (int) ($request->query('branch_id')
            ?? auth()->user()->branch_id
            ?? Branch::first()?->id);

        if (!$branchId) {
            return response()->json([
                'message' => 'No branch found. Please create a branch first.',
            ], 404);
        }

        $setting = $service->findOrInitialize($branchId);

        return response()->json([
            'data'    => $setting,
            'options' => $service->buildOptions(),
        ]);
    }

    public function update(Request $request, StoreSettingsService $service): JsonResponse
    {
        $this->authorize('manage', StoreSetting::class);

        $validated = $request->validate([
            'branch_id'                 => 'required|exists:branches,id',
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

        $setting = $service->findOrInitialize($validated['branch_id']);
        $old = $setting->exists ? $setting->toArray() : null;
        $isNew = !$setting->exists;

        $updated = $service->updateOrCreate(
            $setting,
            $validated,
            $request->only(['store_logo', 'signature'])
        );

        AuditLogService::log(
            $isNew ? 'created' : 'updated',
            StoreSetting::class,
            $updated->id,
            $old,
            $updated->toArray()
        );

        return response()->json([
            'data'    => $updated,
            'options' => $service->buildOptions(),
        ]);
    }
}
