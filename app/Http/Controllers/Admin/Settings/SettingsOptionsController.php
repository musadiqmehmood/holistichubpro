<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use DateTimeZone;
use Illuminate\Http\JsonResponse;

/**
 * Returns all dynamic dropdown data needed by the Settings module in a single
 * authenticated request.  The frontend caches this at app-boot so every
 * settings form can reference live DB data instead of hard-coded arrays.
 *
 * GET /api/admin/settings/options
 */
class SettingsOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            // ── Currency picker (Store Settings) ─────────────────────────────
            'currencies' => Currency::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'symbol']),

            // ── Branch picker (Store Settings) ────────────────────────────────
            'branches' => Branch::orderBy('name')
                ->get(['id', 'name']),

            // ── Tax picker (Tax Groups, future POS) ───────────────────────────
            'taxes' => Tax::active()
                ->orderBy('name')
                ->get(['id', 'name', 'percentage']),

            // ── Tax Group picker (future POS / invoice) ───────────────────────
            'tax_groups' => TaxGroup::active()
                ->orderBy('name')
                ->get(['id', 'name', 'calculated_percentage']),

            // ── Unit picker (Products, Services) ─────────────────────────────
            'units' => Unit::active()
                ->orderBy('name')
                ->get(['id', 'name']),

            // ── Payment type picker (POS, Invoices) ───────────────────────────
            'payment_types' => PaymentType::active()
                ->orderBy('name')
                ->get(['id', 'name']),

            // ── Timezone list (Store Settings) ────────────────────────────────
            'timezones' => DateTimeZone::listIdentifiers(),

            // ── Date format options ───────────────────────────────────────────
            'date_formats' => [
                ['value' => 'Y-m-d',   'label' => 'YYYY-MM-DD (2025-01-31)'],
                ['value' => 'd/m/Y',   'label' => 'DD/MM/YYYY (31/01/2025)'],
                ['value' => 'm/d/Y',   'label' => 'MM/DD/YYYY (01/31/2025)'],
                ['value' => 'd-M-Y',   'label' => 'DD-Mon-YYYY (31-Jan-2025)'],
                ['value' => 'd.m.Y',   'label' => 'DD.MM.YYYY (31.01.2025)'],
                ['value' => 'M d, Y',  'label' => 'Mon DD, YYYY (Jan 31, 2025)'],
            ],

            // ── Time format options ───────────────────────────────────────────
            'time_formats' => [
                ['value' => 'H:i',   'label' => '24-hour (13:05)'],
                ['value' => 'H:i:s', 'label' => '24-hour with seconds (13:05:09)'],
                ['value' => 'h:i A', 'label' => '12-hour (01:05 PM)'],
            ],

            // ── Currency symbol placement ─────────────────────────────────────
            'currency_placements' => [
                ['value' => 'before', 'label' => 'Before amount ($100)'],
                ['value' => 'after',  'label' => 'After amount (100$)'],
            ],

            // ── Encryption types (SMTP) ───────────────────────────────────────
            'smtp_encryptions' => [
                ['value' => 'tls',  'label' => 'TLS (recommended)'],
                ['value' => 'ssl',  'label' => 'SSL'],
                ['value' => 'none', 'label' => 'None'],
            ],
        ]);
    }
}
