<?php

namespace App\Helpers;

use App\Models\StoreSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Global date / time formatting helper.
 *
 * Reads format strings from the current branch's StoreSetting row.
 * Falls back to sensible defaults when no setting exists.
 *
 * Usage:
 *     $formatted = app('datetime')->formatDate(now());
 *     $formatted = app('datetime')->formatTime(now());
 *     $formatted = app('datetime')->formatDateTime(now());
 *     $formatted = app('datetime')->formatCurrency(99.99, 'USD');
 */
class DateTimeHelper
{
    private ?StoreSetting $setting = null;
    private bool $resolved = false;

    /**
     * Lazy-load store settings for the current branch.
     */
    private function setting(): ?StoreSetting
    {
        if ($this->resolved) {
            return $this->setting;
        }

        $branchId = request()->header('X-Branch-ID')
            ?? auth()->user()?->branch_id;

        if (!$branchId) {
            $this->resolved = true;
            return null;
        }

        $this->setting = Cache::remember(
            "store_settings.{$branchId}",
            300, // 5 min — format changes are rare but should propagate quickly
            fn() => StoreSetting::where('branch_id', $branchId)->first()
        );

        $this->resolved = true;
        return $this->setting;
    }

    /** Date format from settings or default. */
    public function dateFormat(): string
    {
        return $this->setting()?->date_format ?? 'Y-m-d';
    }

    /** Time format from settings or default. */
    public function timeFormat(): string
    {
        return $this->setting()?->time_format ?? 'H:i';
    }

    /** Combined date-time format. */
    public function dateTimeFormat(): string
    {
        return $this->dateFormat() . ' ' . $this->timeFormat();
    }

    /** Timezone from settings or default. */
    public function timezone(): string
    {
        return $this->setting()?->timezone ?? config('app.timezone', 'UTC');
    }

    /** Currency decimals from settings. */
    public function decimals(): int
    {
        return $this->setting()?->decimals ?? 2;
    }

    /**
     * Format a Carbon instance as a date string.
     */
    public function formatDate(Carbon $date): string
    {
        return $date->copy()->timezone($this->timezone())->format($this->dateFormat());
    }

    /**
     * Format a Carbon instance as a time string.
     */
    public function formatTime(Carbon $date): string
    {
        return $date->copy()->timezone($this->timezone())->format($this->timeFormat());
    }

    /**
     * Format a Carbon instance as a date-time string.
     */
    public function formatDateTime(Carbon $date): string
    {
        return $date->copy()->timezone($this->timezone())->format($this->dateTimeFormat());
    }

    /**
     * Format a number as currency with the configured decimals.
     */
    public function formatCurrency(float $amount, ?string $symbol = null): string
    {
        $setting = $this->setting();
        $symbol  = $symbol ?? $setting?->currency ?? '$';
        $placement = $setting?->currency_symbol_placement ?? 'before';
        $decimals  = $this->decimals();

        $formatted = number_format($amount, $decimals);

        return $placement === 'before'
            ? "{$symbol}{$formatted}"
            : "{$formatted}{$symbol}";
    }

    /**
     * Parse a user-input date string back to Carbon using the configured format.
     * Returns null on failure.
     */
    public function parseDate(string $input): ?Carbon
    {
        try {
            return Carbon::createFromFormat($this->dateFormat(), $input)
                ->timezone($this->timezone());
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse a user-input date-time string back to Carbon.
     * Returns null on failure.
     */
    public function parseDateTime(string $input): ?Carbon
    {
        try {
            return Carbon::createFromFormat($this->dateTimeFormat(), $input)
                ->timezone($this->timezone());
        } catch (\Throwable $e) {
            return null;
        }
    }
}
