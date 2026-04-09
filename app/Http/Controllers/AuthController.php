<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = 'login.' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->sendRateLimitResponse($throttleKey);
        }

        $user = User::where('email', $request->email)->first();

        if ($user && $user->isLocked()) {
            return $this->sendLockedAccountResponse($user);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->handleFailedLogin($user, $throttleKey);
        }

        if (is_null($user->email_verified_at)) {
            return $this->sendUnverifiedEmailResponse($user);
        }

        if ($user->isPasswordExpired()) {
            return $this->sendPasswordExpiredResponse($user);
        }

        return $this->processSuccessfulLogin($user, $request->ip(), $throttleKey);
    }

    // ❌ register() method DELETED – public registration is disabled

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', 'min:8', 'salon_password', 'not_recent_password'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'errors'  => ['current_password' => ['Current password is incorrect']]
            ], 400);
        }

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'password'            => Hash::make($request->password),
                'password_changed_at' => now(),
            ]);

            PasswordHistory::create([
                'user_id'  => $user->id,
                'password' => $user->password,
            ]);

            $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();
        });

        return response()->json([
            'message' => 'Password changed successfully. Please login again with your new password.',
            'requires_relogin' => true
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['roles.permissions', 'permissions', 'branch']);

        $allPermissions = $user->getAllPermissions()->map(function($permission) {
            return $permission->name;
        })->toArray();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'branch_id' => $user->branch_id,
            'branch' => $user->branch,
            'email_verified_at' => $user->email_verified_at,
            'last_login_at' => $user->last_login_at,
            'last_login_ip' => $user->last_login_ip,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            }),
            'permissions' => $user->permissions->pluck('name'),
            'all_permissions' => $allPermissions,
        ]);
    }

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent']);
    }

    private function sendRateLimitResponse(string $key)
    {
        $seconds = RateLimiter::availableIn($key);
        return response()->json([
            'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
            'retry_after' => $seconds
        ], 429);
    }

    private function sendLockedAccountResponse(User $user)
    {
        $minutes = now()->diffInMinutes($user->locked_until);
        return response()->json([
            'message' => 'Account is locked due to too many failed attempts.',
            'locked_until' => $user->locked_until,
            'retry_after_minutes' => $minutes
        ], 423);
    }

    private function handleFailedLogin(?User $user, string $key)
    {
        if ($user) {
            $user->increment('failed_login_attempts');
            if ($user->failed_login_attempts >= 5) {
                $user->update(['locked_until' => now()->addMinutes(30)]);
            }
        }
        RateLimiter::hit($key);
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    private function sendUnverifiedEmailResponse(User $user)
    {
        return response()->json([
            'message' => 'Please verify your email before logging in.',
            'requires_verification' => true,
            'email' => $user->email
        ], 403);
    }

    private function sendPasswordExpiredResponse(User $user)
    {
        $tempToken = $user->createToken('password-change', ['password:change'])->plainTextToken;
        return response()->json([
            'message' => 'Your password has expired. Please change your password.',
            'requires_password_change' => true,
            'password_token' => $tempToken
        ], 403);
    }

    private function processSuccessfulLogin(User $user, string $ip, string $key)
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        RateLimiter::clear($key);

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->load(['roles.permissions', 'permissions', 'branch']);

        $allPermissions = $user->getAllPermissions()->map(function($permission) {
            return $permission->name;
        })->toArray();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'branch_id' => $user->branch_id,
                'branch' => $user->branch,
                'email_verified_at' => $user->email_verified_at,
                'last_login_at' => $user->last_login_at,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'permissions' => $role->permissions->pluck('name'),
                    ];
                }),
                'permissions' => $user->permissions->pluck('name'),
                'all_permissions' => $allPermissions,
            ],
            'token' => $token,
            'requires_password_change' => false,
        ]);
    }
}
