<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AppleTokenVerifier;
use App\Services\GoogleAvatarSync;
use App\Services\GoogleTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Issue a Sanctum personal access token for mobile / API clients.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        $this->assertUserCanLogin($user);

        return $this->tokenResponse($user, $credentials['device_name'] ?? 'ios-app');
    }

    /**
     * Login / tautkan akun lewat Google ID token dari aplikasi iOS.
     */
    public function google(Request $request, GoogleTokenVerifier $verifier, GoogleAvatarSync $avatarSync): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'picture_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $payload = $verifier->verify($data['id_token']);
        $googleId = (string) ($payload['sub'] ?? '');
        $email = trim((string) ($payload['email'] ?? ''));
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL);
        $picture = $this->resolveGooglePictureUrl(
            $data['picture_url'] ?? null,
            $payload['picture'] ?? null
        );

        if ($googleId === '' || $email === '' || ! $emailVerified) {
            throw ValidationException::withMessages([
                'id_token' => ['Akun Google harus menyediakan email yang sudah diverifikasi.'],
            ]);
        }

        $email = mb_strtolower($email);

        /** @var User|null $user */
        $user = DB::transaction(function () use ($googleId, $email): ?User {
            $byGoogleId = User::query()->where('google_id', $googleId)->lockForUpdate()->first();
            if ($byGoogleId && mb_strtolower((string) $byGoogleId->email) !== $email) {
                throw ValidationException::withMessages([
                    'id_token' => ['Identitas Google tidak cocok dengan email akun WOFINS.'],
                ]);
            }

            $byEmail = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            if ($byEmail?->google_id && ! hash_equals((string) $byEmail->google_id, $googleId)) {
                throw ValidationException::withMessages([
                    'id_token' => ['Email ini sudah ditautkan ke akun Google lain.'],
                ]);
            }

            $matched = $byGoogleId ?? $byEmail;
            if (! $matched) {
                return null;
            }

            $this->assertUserCanLogin($matched);

            $matched->forceFill([
                'google_id' => $matched->google_id ?: $googleId,
                'email_verified_at' => $matched->email_verified_at ?: now(),
            ])->save();

            return $matched->fresh();
        });

        if ($user) {
            $this->assertUserCanLogin($user);

            // Ambil foto Google sebagai avatar default jika user belum punya foto
            $avatarSync->sync($user, $picture);
            $user->refresh();
        } else {
            throw ValidationException::withMessages([
                'id_token' => ['Akun Google belum terdaftar di WOFINS. Hubungi administrator company Anda.'],
            ]);
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'ios-wofins-google');
    }

    public function apple(Request $request, AppleTokenVerifier $verifier): JsonResponse
    {
        $data = $request->validate([
            'identity_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'account_email' => ['nullable', 'email'],
            'account_password' => ['nullable', 'string', 'required_with:account_email'],
        ]);

        $payload = $verifier->verify($data['identity_token']);
        $appleId = (string) $payload['sub'];
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        /** @var User|null $user */
        $user = DB::transaction(function () use ($appleId, $email, $emailVerified, $data): ?User {
            $byAppleId = User::query()->where('apple_id', $appleId)->lockForUpdate()->first();
            if ($byAppleId) {
                $this->assertUserCanLogin($byAppleId);

                return $byAppleId;
            }

            $byEmail = null;
            if ($email !== '' && $emailVerified) {
                $byEmail = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            }

            if (! $byEmail && filled($data['account_email'] ?? null)) {
                $accountEmail = mb_strtolower(trim((string) $data['account_email']));
                $candidate = User::query()->whereRaw('LOWER(email) = ?', [$accountEmail])->lockForUpdate()->first();
                if ($candidate && Hash::check((string) ($data['account_password'] ?? ''), $candidate->password)) {
                    $byEmail = $candidate;
                }
            }

            if (! $byEmail) {
                return null;
            }

            if ($byEmail->apple_id && ! hash_equals((string) $byEmail->apple_id, $appleId)) {
                throw ValidationException::withMessages([
                    'identity_token' => ['Email ini sudah ditautkan ke akun Apple lain.'],
                ]);
            }

            $this->assertUserCanLogin($byEmail);
            $byEmail->forceFill([
                'apple_id' => $appleId,
                'email_verified_at' => $byEmail->email_verified_at ?: now(),
            ])->save();

            return $byEmail->fresh();
        });

        if (! $user) {
            throw ValidationException::withMessages([
                'identity_token' => ['Akun Apple belum terhubung. Isi email dan password WOFINS, lalu pilih Sign in with Apple sekali lagi untuk menautkan akun.'],
            ]);
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'ios-wofins-apple');
    }

    /**
     * Hanya terima URL foto dari host Google yang dikenal.
     */
    private function resolveGooglePictureUrl(mixed $fromClient, mixed $fromToken): ?string
    {
        foreach ([$fromClient, $fromToken] as $candidate) {
            $url = trim((string) $candidate);
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if ($host === '' || ! str_ends_with($host, 'googleusercontent.com')) {
                continue;
            }

            return $url;
        }

        return null;
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    private function assertUserCanLogin(User $user): void
    {
        if (in_array($user->status, ['terminated', 'inactive'], true)) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Hubungi administrator.'],
            ]);
        }

        if ($user->isExpired()) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah kedaluwarsa. Hubungi administrator.'],
            ]);
        }

        if (! $user->hasRole('super_admin')) {
            if (! $user->company || $user->company->isDeactivated()) {
                throw ValidationException::withMessages([
                    'email' => ['Perusahaan Anda tidak aktif. Hubungi administrator.'],
                ]);
            }

            // Paket company expired: tetap boleh login (sama seperti web),
            // lalu iOS menampilkan layar perpanjang paket.
        }
    }

    private function tokenResponse(User $user, string $deviceName): JsonResponse
    {
        $expiresAt = now()->addDays(max(1, (int) config('sanctum.mobile_token_expiration_days', 30)));
        $token = $user->createToken($deviceName, ['mobile'], $expiresAt)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => new UserResource($user->loadMissing(['roles', 'company'])),
        ]);
    }
}
