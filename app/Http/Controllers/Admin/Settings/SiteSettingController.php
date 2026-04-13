<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $setting = SiteSetting::firstOrCreate([], ['site_name' => config('app.name', 'HolisticHubPro')]);
        return response()->json($setting);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage', SiteSetting::class);

        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg,gif,bmp|max:20480',
        ]);

        $setting = SiteSetting::firstOrCreate([], ['site_name' => config('app.name', 'HolisticHubPro')]);
        $old = $setting->toArray();

        if ($request->hasFile('site_logo')) {
            if ($setting->site_logo) {
                Storage::disk('public')->delete($setting->site_logo);
            }
            $validated['site_logo'] = $request->file('site_logo')->store('settings/site', 'public');
        }

        $setting->update($validated);

        AuditLogService::log('updated', SiteSetting::class, $setting->id, $old, $setting->fresh()->toArray());

        return response()->json($setting->fresh());
    }
}
