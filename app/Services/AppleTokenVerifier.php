<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class AppleTokenVerifier
{
    /**
     * @return array<string, mixed>
     */
    public function verify(string $identityToken): array
    {
        try {
            $keys = Cache::remember('apple.signin.jwks', 3600, function (): array {
                $response = Http::timeout(8)
                    ->retry(2, 200)
                    ->get('https://appleid.apple.com/auth/keys')
                    ->throw();

                return $response->json();
            });

            $payload = (array) JWT::decode($identityToken, JWK::parseKeySet($keys));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'identity_token' => ['Token Sign in with Apple tidak valid.'],
            ]);
        }

        $audience = $payload['aud'] ?? null;
        $allowedAudiences = array_values(array_filter([
            (string) config('services.apple.client_id', 'id.wofins.app'),
            (string) config('services.apple.web_client_id'),
        ], fn (string $value): bool => $value !== ''));

        $audienceMatches = is_array($audience)
            ? count(array_intersect($allowedAudiences, $audience)) > 0
            : in_array((string) $audience, $allowedAudiences, true);

        if (($payload['iss'] ?? null) !== 'https://appleid.apple.com' || ! $audienceMatches) {
            throw ValidationException::withMessages([
                'identity_token' => ['Token Apple tidak ditujukan untuk aplikasi WOFINS.'],
            ]);
        }

        if (blank($payload['sub'] ?? null)) {
            throw ValidationException::withMessages([
                'identity_token' => ['Identitas akun Apple tidak tersedia.'],
            ]);
        }

        return $payload;
    }
}
