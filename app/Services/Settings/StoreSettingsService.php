<?php

namespace App\Services\Settings;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\StoreSetting;
use DateTimeZone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Encapsulates all business logic for store settings —
 * finding by branch, logo handling, and option list building.
 */
class StoreSettingsService
{
    /**
     * Find the store setting record for a given branch.
     * Returns an empty model (not persisted) if none exists yet.
     */
    public function findOrInitialize(int $branchId): StoreSetting
    {
        return StoreSetting::where('branch_id', $branchId)->first()
            ?? new StoreSetting(['branch_id' => $branchId]);
    }

    /**
     * Update or create store settings for a branch.
     * Handles logo/signature uploads and cleans up old files.
     */
    public function updateOrCreate(StoreSetting $setting, array $validated, array $files): StoreSetting
    {
        $this->handleFileUpload($setting, $validated, $files, 'store_logo', 'settings/store');
        $this->handleFileUpload($setting, $validated, $files, 'signature', 'settings/store');

        if (isset($validated['currency'])) {
            $validated['currency'] = strtoupper($validated['currency']);
        }

        $setting->fill($validated);
        $setting->save();

        Cache::forget('settings_options.branches');

        return $setting->fresh()->load('branch');
    }

    /**
     * Build the options payload sent alongside the setting record.
     * Used by the frontend to render dropdowns without extra round-trips.
     */
    public function buildOptions(): array
    {
        return [
            'currencies' => Cache::remember('store_options.currencies', 3600, fn() =>
            Currency::active()->orderBy('name')->get(['id', 'name', 'code', 'symbol'])
            ),
            'branches' => Cache::remember('store_options.branches', 3600, fn() =>
            Branch::orderBy('name')->get(['id', 'name'])
            ),
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

    /**
     * Handle a single file upload: delete old file, store new one.
     */
    private function handleFileUpload(
        StoreSetting $setting,
        array &$validated,
        array $files,
        string $field,
        string $diskPath,
    ): void {
        if (!isset($files[$field]) || !($files[$field] instanceof UploadedFile)) {
            return;
        }

        $oldPath = $setting->getAttribute($field);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $validated[$field] = $files[$field]->store($diskPath, 'public');
    }
}
