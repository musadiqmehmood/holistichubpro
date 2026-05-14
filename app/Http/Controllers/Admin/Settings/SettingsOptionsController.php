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
    private const ENUMS = [
        'date_formats' => [
            ['value' => 'Y-m-d',   'label' => 'YYYY-MM-DD (2025-01-31)',  'group' => 'Standard'],
            ['value' => 'd/m/Y',   'label' => 'DD/MM/YYYY (31/01/2025)',  'group' => 'European'],
            ['value' => 'm/d/Y',   'label' => 'MM/DD/YYYY (01/31/2025)',  'group' => 'US'],
            ['value' => 'd-M-Y',   'label' => 'DD-Mon-YYYY (31-Jan-2025)', 'group' => 'Standard'],
            ['value' => 'd.m.Y',   'label' => 'DD.MM.YYYY (31.01.2025)',  'group' => 'European'],
            ['value' => 'M d, Y',  'label' => 'Mon DD, YYYY (Jan 31, 2025)', 'group' => 'US'],
        ],
        'time_formats' => [
            ['value' => 'H:i',   'label' => '24-hour (13:05)',          'group' => '24h'],
            ['value' => 'H:i:s', 'label' => '24-hour with seconds (13:05:09)', 'group' => '24h'],
            ['value' => 'h:i A', 'label' => '12-hour (01:05 PM)',       'group' => '12h'],
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
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $branchId = $request->header('X-Branch-ID') ?? auth()->user()->branch_id;

        // Single enum search — lightweight request
        $type = $request->query('type');
        if ($type && isset(self::ENUMS[$type])) {
            return response()->json([
                'success' => true,
                'data' => $this->filterEnums(self::ENUMS[$type], $request->query('search')),
            ]);
        }

        // Full payload
        return response()->json([
            'site_settings'  => SiteSetting::first(),
            'store_settings' => StoreSetting::where('branch_id', $branchId)->first(),

            'currencies'    => Cache::remember('settings_options.currencies', 3600, fn() => Currency::active()->orderBy('name')->get(['id', 'name', 'code', 'symbol'])),
            'branches'      => Cache::remember('settings_options.branches', 3600, fn() => Branch::orderBy('name')->get(['id', 'name'])),
            'taxes'         => Cache::remember('settings_options.taxes', 3600, fn() => Tax::active()->orderBy('name')->get(['id', 'name', 'percentage'])),
            'tax_groups'    => Cache::remember('settings_options.tax_groups', 3600, fn() => TaxGroup::active()->orderBy('name')->get(['id', 'name', 'calculated_percentage'])),
            'units'         => Cache::remember('settings_options.units', 3600, fn() => Unit::active()->orderBy('name')->get(['id', 'name'])),
            'payment_types' => Cache::remember('settings_options.payment_types', 3600, fn() => PaymentType::active()->orderBy('name')->get(['id', 'name'])),

            'timezones'           => $this->filterTimezones($request->query('search')),
            'date_formats'        => $this->filterEnums(self::ENUMS['date_formats'], $request->query('search')),
            'time_formats'        => $this->filterEnums(self::ENUMS['time_formats'], $request->query('search')),
            'currency_placements' => $this->filterEnums(self::ENUMS['currency_placements'], $request->query('search')),
            'smtp_encryptions'    => $this->filterEnums(self::ENUMS['smtp_encryptions'], $request->query('search')),
        ]);
    }

    private function filterEnums(array $items, ?string $search): array
    {
        if (empty($search)) return $items;

        $needle = strtolower($search);
        return array_values(array_filter(
            $items, fn(array $item) => str_contains(strtolower($item['label']), $needle)
        ));
    }

    private function filterTimezones(?string $search): array
    {
        $zones = DateTimeZone::listIdentifiers();
        if (empty($search)) return $zones;

        $needle = strtolower($search);
        return array_values(array_filter($zones, fn(string $tz) => str_contains(strtolower($tz), $needle)));
    }
}
