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
            'settings' => 'required|array',
            // ✅ no restriction on value types – booleans, numbers, strings are all allowed
        ]);

        $setting = UserDesignSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['settings' => $validated['settings']]
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
