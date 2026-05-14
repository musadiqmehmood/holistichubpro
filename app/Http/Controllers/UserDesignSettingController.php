<?php

namespace App\Http\Controllers;

use App\Models\UserDesignSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDesignSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $setting = UserDesignSetting::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['settings' => $this->defaultSettings()]
        );

        return response()->json($setting->settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings'                        => 'required|array',
            // Every key below is whitelisted — anything else is silently ignored
            'settings.themeMode'              => 'nullable|in:light,dark',
            'settings.primaryColor'           => 'nullable|string|max:7|starts_with:#',
            'settings.secondaryColor'         => 'nullable|string|max:7|starts_with:#',
            'settings.backgroundColor'        => 'nullable|string|max:7|starts_with:#',
            'settings.surfaceColor'           => 'nullable|string|max:7|starts_with:#',
            'settings.textPrimary'            => 'nullable|string|max:7|starts_with:#',
            'settings.textSecondary'          => 'nullable|string|max:7|starts_with:#',
            'settings.headingColor'           => 'nullable|string|max:7|starts_with:#',
            'settings.subheadingColor'        => 'nullable|string|max:7|starts_with:#',
            'settings.paragraphColor'         => 'nullable|string|max:7|starts_with:#',
            'settings.borderColor'            => 'nullable|string|max:7|starts_with:#',
            'settings.menuBg'                 => 'nullable|string|max:7|starts_with:#',
            'settings.menuText'               => 'nullable|string|max:7|starts_with:#',
            'settings.btnBg'                  => 'nullable|string|max:7|starts_with:#',
            'settings.btnText'              => 'nullable|string|max:7|starts_with:#',
            'settings.btnHoverBg'           => 'nullable|string|max:7|starts_with:#',
            'settings.fontFamily'           => 'nullable|string|max:100',
            'settings.fontSizeBase'         => 'nullable|string|max:10',
            'settings.fontSizeHeadingScale' => 'nullable|string|max:10',
            'settings.bodyLineHeight'       => 'nullable|string|max:10',
            'settings.letterSpacing'        => 'nullable|string|max:20',
        ]);

        // Merge validated subset with defaults so unknown keys never reach DB
        $settings = array_intersect_key(
            $validated['settings'],
            $this->defaultSettings()
        );

        $setting = UserDesignSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['settings' => array_merge($this->defaultSettings(), $settings)]
        );

        return response()->json([
            'message'  => 'Design settings saved.',
            'settings' => $setting->settings,
        ]);
    }

    private function defaultSettings(): array
    {
        return [
            'themeMode'              => 'light',
            'primaryColor'           => '#6366f1',
            'secondaryColor'         => '#8b5cf6',
            'backgroundColor'        => '#f8fafc',
            'surfaceColor'           => '#ffffff',
            'textPrimary'            => '#0f172a',
            'textSecondary'          => '#475569',
            'headingColor'           => '#0f172a',
            'subheadingColor'        => '#334155',
            'paragraphColor'         => '#475569',
            'borderColor'            => '#e2e8f0',
            'menuBg'                 => '#ffffff',
            'menuText'               => '#0f172a',
            'btnBg'                  => '#6366f1',
            'btnText'                => '#ffffff',
            'btnHoverBg'             => '#4f46e5',
            'fontFamily'             => 'Montserrat, Roboto, system-ui, sans-serif',
            'fontSizeBase'           => '16px',
            'fontSizeHeadingScale'   => '1.25',
            'bodyLineHeight'         => '1.5',
            'letterSpacing'          => 'normal',
        ];
    }
}
