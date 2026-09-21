<?php

namespace App\Services;

use App\Support\AppleIapProducts;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Verifikasi JWS StoreKit 2 / App Store Server Notifications V2.
 */
class AppleJwsVerifier
{
    /**
     * @return array<string, mixed>
     */
    public function decodeAndVerify(string $jws): array
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Format transaksi Apple tidak valid.'],
            ]);
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $headerJson = $this->base64UrlDecode($headerB64);
        $payloadJson = $this->base64UrlDecode($payloadB64);
        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (! is_array($header) || ! is_array($payload)) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Payload transaksi Apple tidak dapat dibaca.'],
            ]);
        }

        // StoreKit Configuration (Xcode) memakai JWS lokal tanpa rantai sertifikat Apple produksi.
        if ($this->isXcodeStoreKitPayload($payload) && app()->environment(['local', 'testing'])) {
            return $payload;
        }

        if ($this->shouldSkipCrypto()) {
            return $payload;
        }

        $x5c = $header['x5c'] ?? null;
        if (! is_array($x5c) || $x5c === []) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Sertifikat transaksi Apple tidak ditemukan. Uji Xcode StoreKit hanya didukung di API local.'],
            ]);
        }

        $leafPem = $this->x5cToPem((string) $x5c[0]);
        $publicKey = openssl_pkey_get_public($leafPem);
        if ($publicKey === false) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Kunci publik Apple tidak valid.'],
            ]);
        }

        $signature = $this->joseEs256SignatureToDer(
            $this->base64UrlDecode($signatureB64, raw: true)
        );
        $signedData = $headerB64.'.'.$payloadB64;
        $ok = openssl_verify($signedData, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Tanda tangan transaksi Apple tidak valid.'],
            ]);
        }

        $this->assertAppleCertificateChain($x5c);

        return $payload;
    }

    /**
     * JWS ES256 memakai R||S (64 byte); OpenSSL membutuhkan DER.
     */
    private function joseEs256SignatureToDer(string $raw): string
    {
        if (strlen($raw) !== 64) {
            return $raw;
        }

        $r = ltrim(substr($raw, 0, 32), "\x00");
        $s = ltrim(substr($raw, 32, 32), "\x00");
        if ($r === '' || ord($r[0]) > 0x7f) {
            $r = "\x00".$r;
        }
        if ($s === '' || ord($s[0]) > 0x7f) {
            $s = "\x00".$s;
        }

        return "\x30".chr(2 + strlen($r) + 2 + strlen($s))
            ."\x02".chr(strlen($r)).$r
            ."\x02".chr(strlen($s)).$s;
    }

    /**
     * @param  list<mixed>  $x5c
     */
    private function assertAppleCertificateChain(array $x5c): void
    {
        $leafPem = $this->x5cToPem((string) $x5c[0]);
        $parsed = openssl_x509_parse($leafPem);
        $oid = is_array($parsed) ? ($parsed['extensions']['1.2.840.113635.100.6.11.1'] ?? null) : null;

        // StoreKit transaction certs include Apple OID; if missing, still require CN/O Apple.
        $org = is_array($parsed) ? (string) ($parsed['subject']['O'] ?? '') : '';
        if ($oid === null && stripos($org, 'Apple') === false) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Sertifikat transaksi bukan dari Apple.'],
            ]);
        }
    }

    private function shouldSkipCrypto(): bool
    {
        if (! (bool) config('wofins.apple_iap.skip_verify', false)) {
            return false;
        }

        return app()->environment(['local', 'testing']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isXcodeStoreKitPayload(array $payload): bool
    {
        $environment = strtolower((string) ($payload['environment'] ?? ''));

        return in_array($environment, ['xcode', 'local', 'storekit'], true);
    }

    private function x5cToPem(string $x5c): string
    {
        $body = trim(chunk_split($x5c, 64, "\n"));

        return "-----BEGIN CERTIFICATE-----\n{$body}\n-----END CERTIFICATE-----\n";
    }

    private function base64UrlDecode(string $value, bool $raw = false): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new RuntimeException('Base64url decode failed.');
        }

        return $decoded;
    }

    /**
     * Opsional: unduh root Apple (untuk hardening lanjutan).
     */
    public function fetchAppleRootCertificates(): array
    {
        $url = 'https://www.apple.com/certificateauthority/AppleRootCA-G3.cer';
        $response = Http::timeout(10)->get($url);
        if (! $response->successful()) {
            return [];
        }

        return [$response->body()];
    }

    public function assertBundleId(array $payload): void
    {
        $bundle = (string) ($payload['bundleId'] ?? '');
        $expected = (string) config('wofins.apple_iap.bundle_id', AppleIapProducts::BUNDLE_ID);
        if ($bundle !== '' && ! hash_equals($expected, $bundle)) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Bundle ID transaksi tidak cocok.'],
            ]);
        }
    }
}
