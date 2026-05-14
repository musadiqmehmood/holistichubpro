<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\SmtpSetting;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\Email;

class SmtpSettingController extends Controller
{
    use ApiResponse;
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

        return $this->success($this->masked($setting));
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

        // Only update password when client sends a non-null, non-empty value.
        $incomingPassword = $validated['password'] ?? null;
        if ($incomingPassword === null || trim($incomingPassword) === '') {
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

        return $this->success($this->masked($setting->fresh()));
    }

    /**
     * Send a test email using the currently saved SMTP settings.
     * Creates a temporary Symfony transport — global mail config is NEVER touched.
     */
    public function test(): JsonResponse
    {
        $setting = SmtpSetting::first();

        if (!$setting || !$setting->status) {
            return $this->unprocessable('SMTP is not enabled.');
        }

        if (empty($setting->host) || empty($setting->username)) {
            return $this->unprocessable('SMTP host and username must be configured before testing.');
        }

        $user = Auth::user();
        if (!$user || empty($user->email)) {
            return $this->unprocessable('Authenticated user email not found.');
        }
        $recipient = $user->email;

        try {
            // Build a temporary SMTP transport without mutating global config.
            $stream = new SocketStream();

            $encryption = $setting->encryption === 'none' ? null : $setting->encryption;
            if ($encryption) {
                $stream->setHost($setting->host);
                $stream->setPort($setting->port);
                $stream->setTls(true);
            }

            $transport = new EsmtpTransport(
                host: $setting->host,
                port: $setting->port,
                tls: $encryption !== null,
                stream: $stream
            );
            $transport->setUsername($setting->username);
            $transport->setPassword($setting->password);

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new Email())
                ->from(config('mail.from.address', 'noreply@example.com'))
                ->to($recipient)
                ->subject(config('app.name') . ' — SMTP Test Email')
                ->text('This is a test email from ' . config('app.name') . ' to verify your SMTP configuration.');

            $mailer->send($email);

            AuditLogService::log('smtp_test_sent', SmtpSetting::class, $setting->id, null, [
                'recipient' => $recipient,
                'host'      => $setting->host,
                'port'      => $setting->port,
            ]);

            return $this->success(null, 'Test email sent to ' . $recipient);
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

            return $this->serverError('Send failed: ' . $e->getMessage());
        }
    }

    /**
     * Strip the raw password from the response.
     * Frontend should treat password === null as "not configured"
     * and any non-null value as "configured (hidden)".
     */
    private function masked(SmtpSetting $setting): array
    {
        $data             = $setting->toArray();
        $data['password'] = $setting->password ? '__configured__' : null;
        return $data;
    }
}
