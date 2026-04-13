<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\SmtpSetting;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SmtpSettingController extends Controller
{
    private const SENTINEL = '••••••••';

    public function show(): JsonResponse
    {
        $setting = SmtpSetting::firstOrCreate([], [
            'status'     => false,
            'host'       => '',
            'port'       => 587,
            'username'   => '',
            'password'   => '',
            'encryption' => 'tls',
        ]);

        return response()->json($this->masked($setting));
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage', SmtpSetting::class);

        $validated = $request->validate([
            'status'     => 'sometimes|boolean',
            'host'       => 'required|string|max:255',
            'port'       => 'required|integer|min:1|max:65535',
            'username'   => 'required|string|max:255',
            'password'   => 'nullable|string|max:500',
            'encryption' => 'required|in:tls,ssl,none',
        ]);

        $setting = SmtpSetting::firstOrCreate([], [
            'status'     => false,
            'host'       => '',
            'port'       => 587,
            'username'   => '',
            'password'   => '',
            'encryption' => 'tls',
        ]);

        $old = $this->masked($setting);

        $incomingPassword = $validated['password'] ?? null;
        if ($incomingPassword === self::SENTINEL || empty($incomingPassword)) {
            unset($validated['password']);
        }

        $setting->fill(collect($validated)->except('password')->toArray());

        if (isset($validated['password'])) {
            $setting->password = $validated['password'];
        }

        $setting->save();

        AuditLogService::log(
            'updated',
            SmtpSetting::class,
            $setting->id,
            $old,
            $this->masked($setting->fresh())
        );

        return response()->json($this->masked($setting->fresh()));
    }

    /**
     * Send a test email using the currently saved SMTP settings.
     * Temporarily overrides the mail configuration, sends the email, and restores the original config.
     */
    public function test(): JsonResponse
    {
        $setting = SmtpSetting::first();

        if (!$setting || !$setting->status) {
            return response()->json(['message' => 'SMTP is not enabled.'], 422);
        }

        if (empty($setting->host) || empty($setting->username)) {
            return response()->json(['message' => 'SMTP host and username must be configured before testing.'], 422);
        }

        $user = Auth::user();
        if (!$user || empty($user->email)) {
            return response()->json(['message' => 'Authenticated user email not found.'], 422);
        }
        $recipient = $user->email;

        // Save original mail configuration
        $originalMailConfig = config('mail');
        $originalMailerConfig = config('mail.mailers.smtp');

        try {
            // Override mail configuration with SMTP settings
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp' => [
                    'transport' => 'smtp',
                    'host'      => $setting->host,
                    'port'      => $setting->port,
                    'encryption' => $setting->encryption === 'none' ? null : $setting->encryption,
                    'username'  => $setting->username,
                    'password'  => $setting->password,
                    'timeout'   => null,
                    'auth_mode' => null,
                ],
            ]);

            // Send test email
            Mail::raw(
                'This is a test email from ' . config('app.name') . ' to verify your SMTP configuration.',
                function ($msg) use ($recipient) {
                    $msg->from(config('mail.from.address'), config('mail.from.name'))
                        ->to($recipient)
                        ->subject(config('app.name') . ' — SMTP Test Email');
                }
            );

            AuditLogService::log('smtp_test_sent', SmtpSetting::class, $setting->id, null, [
                'recipient' => $recipient,
                'host'      => $setting->host,
                'port'      => $setting->port,
            ]);

            return response()->json(['message' => 'Test email sent to ' . $recipient]);
        } catch (\Throwable $e) {
            Log::error('SMTP test failed', [
                'error' => $e->getMessage(),
                'host' => $setting->host,
                'port' => $setting->port,
            ]);

            AuditLogService::log('smtp_test_failed', SmtpSetting::class, $setting->id, null, [
                'recipient' => $recipient,
                'error'     => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Send failed: ' . $e->getMessage()], 500);
        } finally {
            // Restore original mail configuration
            config(['mail' => $originalMailConfig]);
            config(['mail.mailers.smtp' => $originalMailerConfig]);
        }
    }

    private function masked(SmtpSetting $setting): array
    {
        $data             = $setting->toArray();
        $data['password'] = $setting->password ? self::SENTINEL : null;
        return $data;
    }
}
