<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\SiteSetting; // ADDED: To include current settings
use App\Models\StoreSetting; // ADDED: To include current settings
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsOptionsController extends Controller
{
    /**
     * GET /api/admin/settings/options
     *
     * UPDATED: Refactored to include current site and store settings.
     * This allows the frontend to sync everything in one go.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // CHANGE: Detect branch from header or user context
        $branchId = $request->header('X-Branch-ID') ?? auth()->user()->branch_id;

        return response()->json([
            // ── ADDED: Current Settings ─────────────────────────────────────────────
            'site_settings'  => SiteSetting::first(),
            'store_settings' => StoreSetting::where('branch_id', $branchId)->first(),

            // ── Dropdown Options ─────────────────────────────────────────────
            'currencies' => Currency::active()->orderBy('name')->get(['id', 'name', 'code', 'symbol']),
            'branches'   => Branch::orderBy('name')->get(['id', 'name']),
            'taxes'      => Tax::active()->orderBy('name')->get(['id', 'name', 'percentage']),
            'tax_groups' => TaxGroup::active()->orderBy('name')->get(['id', 'name', 'calculated_percentage']),
            'units'      => Unit::active()->orderBy('name')->get(['id', 'name']),
            'payment_types' => PaymentType::active()->orderBy('name')->get(['id', 'name']),
            'timezones'  => DateTimeZone::listIdentifiers(),

            // ── Format Enums ─────────────────────────────────────────────────
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
            'smtp_encryptions' => [
                ['value' => 'tls',  'label' => 'TLS (recommended)'],
                ['value' => 'ssl',  'label' => 'SSL'],
                ['value' => 'none', 'label' => 'None'],
            ],
        ]);
    }
}
