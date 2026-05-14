<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\SiteSetting;
use App\Models\StoreSetting;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsOptionsController extends Controller
{
    /**
     * GET /api/admin/settings/options
     *
     * Returns all dropdown data and current settings.
     * Reference tables are cached for 1 hour — they rarely change.
     * Store settings are NOT cached because they are branch-specific
     * and change frequently.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $branchId = $request->header('X-Branch-ID') ?? auth()->user()->branch_id;

        return response()->json([
            // ── Current Settings (not cached — branch-specific, change frequently) ──
            'site_settings'  => SiteSetting::first(),
            'store_settings' => StoreSetting::where('branch_id', $branchId)->first(),

            // ── Reference tables (cached 1 hour) ─────────────────────────────
            'currencies'    => Cache::remember('settings_options.currencies', 3600, fn() =>
            Currency::active()->orderBy('name')->get(['id', 'name', 'code', 'symbol'])
            ),
            'branches'      => Cache::remember('settings_options.branches', 3600, fn() =>
            Branch::orderBy('name')->get(['id', 'name'])
            ),
            'taxes'         => Cache::remember('settings_options.taxes', 3600, fn() =>
            Tax::active()->orderBy('name')->get(['id', 'name', 'percentage'])
            ),
            'tax_groups'    => Cache::remember('settings_options.tax_groups', 3600, fn() =>
            TaxGroup::active()->orderBy('name')->get(['id', 'name', 'calculated_percentage'])
            ),
            'units'         => Cache::remember('settings_options.units', 3600, fn() =>
            Unit::active()->orderBy('name')->get(['id', 'name'])
            ),
            'payment_types' => Cache::remember('settings_options.payment_types', 3600, fn() =>
            PaymentType::active()->orderBy('name')->get(['id', 'name'])
            ),

            // ── Static enums (computed, no cache needed) ─────────────────────
            'timezones'           => DateTimeZone::listIdentifiers(),
            'date_formats'        => [
                ['value' => 'Y-m-d',   'label' => 'YYYY-MM-DD (2025-01-31)'],
                ['value' => 'd/m/Y',   'label' => 'DD/MM/YYYY (31/01/2025)'],
                ['value' => 'm/d/Y',   'label' => 'MM/DD/YYYY (01/31/2025)'],
                ['value' => 'd-M-Y',   'label' => 'DD-Mon-YYYY (31-Jan-2025)'],
                ['value' => 'd.m.Y',   'label' => 'DD.MM.YYYY (31.01.2025)'],
                ['value' => 'M d, Y',  'label' => 'Mon DD, YYYY (Jan 31, 2025)'],
            ],
            'time_formats'        => [
                ['value' => 'H:i',   'label' => '24-hour (13:05)'],
                ['value' => 'H:i:s', 'label' => '24-hour with seconds (13:05:09)'],
                ['value' => 'h:i A', 'label' => '12-hour (01:05 PM)'],
            ],
            'currency_placements' => [
                ['value' => 'before', 'label' => 'Before amount ($100)'],
                ['value' => 'after',  'label' => 'After amount (100$)'],
            ],
            'smtp_encryptions'    => [
                ['value' => 'tls',  'label' => 'TLS (recommended)'],
                ['value' => 'ssl',  'label' => 'SSL'],
                ['value' => 'none', 'label' => 'None'],
            ],
        ]);
    }
}
