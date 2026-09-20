<?php

namespace App\Services;

use App\Models\AppleIapTransaction;
use App\Models\Company;
use App\Models\User;
use App\Support\AppleIapProducts;
use App\Support\CompanySubscription;
use App\Support\PricingPlans;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppleIapService
{
    public function __construct(
        private readonly AppleJwsVerifier $jws,
    ) {}

    /**
     * @return array{transaction: AppleIapTransaction, company: Company}
     */
    public function verifyAndActivate(User $user, string $signedTransaction): array
    {
        if (! CompanySubscription::canManageSubscription($user)) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Hanya pemilik perusahaan yang dapat mengelola paket langganan.'],
            ]);
        }

        $company = $user->company;
        if (! $company instanceof Company) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Akun belum terhubung ke perusahaan.'],
            ]);
        }

        $payload = $this->jws->decodeAndVerify($signedTransaction);
        $this->jws->assertBundleId($payload);

        return $this->applyTransactionPayload($user, $company, $payload, $signedTransaction);
    }

    /**
     * Restore: aktifkan dari transaksi yang masih berlaku (payload dari client).
     *
     * @param  list<string>  $signedTransactions
     * @return array{transaction: AppleIapTransaction, company: Company}|null
     */
    public function restore(User $user, array $signedTransactions): ?array
    {
        if (! CompanySubscription::canManageSubscription($user)) {
            throw ValidationException::withMessages([
                'signed_transactions' => ['Hanya pemilik perusahaan yang dapat memulihkan paket langganan.'],
            ]);
        }

        $company = $user->company;
        if (! $company instanceof Company) {
            throw ValidationException::withMessages([
                'signed_transactions' => ['Akun belum terhubung ke perusahaan.'],
            ]);
        }

        $best = null;
        foreach ($signedTransactions as $jws) {
            if (! is_string($jws) || trim($jws) === '') {
                continue;
            }
            try {
                $payload = $this->jws->decodeAndVerify($jws);
                $this->jws->assertBundleId($payload);
                $expiresMs = (int) ($payload['expiresDate'] ?? 0);
                if ($expiresMs > 0 && $expiresMs < (int) (microtime(true) * 1000)) {
                    continue;
                }
                $applied = $this->applyTransactionPayload($user, $company, $payload, $jws);
                $expires = $applied['transaction']->expires_at;
                if ($best === null || ($expires && $best['transaction']->expires_at && $expires->greaterThan($best['transaction']->expires_at))) {
                    $best = $applied;
                } elseif ($best === null) {
                    $best = $applied;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $best;
    }

    /**
     * Dipakai webhook ASN V2 (renew / expire / refund).
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyServerPayload(array $payload, ?string $rawJws = null): ?AppleIapTransaction
    {
        $productId = (string) ($payload['productId'] ?? '');
        $meta = AppleIapProducts::resolve($productId);
        if ($meta === null) {
            return null;
        }

        $originalId = (string) ($payload['originalTransactionId'] ?? $payload['transactionId'] ?? '');
        if ($originalId === '') {
            return null;
        }

        $existing = AppleIapTransaction::query()
            ->where('original_transaction_id', $originalId)
            ->orderByDesc('id')
            ->first();

        $company = $existing?->company;
        $user = $existing?->user;

        if (! $company instanceof Company) {
            return null;
        }

        $revoked = isset($payload['revocationDate']) && (int) $payload['revocationDate'] > 0;
        $expiresAt = $this->expiresAtFromPayload($payload, $meta['billing']);

        return DB::transaction(function () use ($payload, $rawJws, $meta, $productId, $originalId, $company, $user, $revoked, $expiresAt) {
            $transactionId = (string) ($payload['transactionId'] ?? $originalId);

            $row = AppleIapTransaction::query()->updateOrCreate(
                ['transaction_id' => $transactionId],
                [
                    'original_transaction_id' => $originalId,
                    'user_id' => $user?->id,
                    'company_id' => $company->id,
                    'product_id' => $productId,
                    'plan_key' => $meta['plan'],
                    'billing' => $meta['billing'],
                    'environment' => (string) ($payload['environment'] ?? null),
                    'bundle_id' => (string) ($payload['bundleId'] ?? null),
                    'purchased_at' => $this->msToCarbon($payload['purchaseDate'] ?? null),
                    'expires_at' => $expiresAt,
                    'revocation_reason' => $revoked ? (string) ($payload['revocationReason'] ?? 'revoked') : null,
                    'revoked_at' => $revoked ? $this->msToCarbon($payload['revocationDate'] ?? null) : null,
                    'raw_payload' => $payload,
                ]
            );

            if ($revoked || ($expiresAt && $expiresAt->isPast())) {
                // Biarkan expired_at company mengikuti transaksi; jangan hapus plan key.
                $company->forceFill([
                    'subscription_expires_at' => $expiresAt?->copy()->endOfDay() ?? now()->subDay()->endOfDay(),
                ])->save();
                CompanySubscription::forgetCache($company->id);

                return $row;
            }

            CompanySubscription::activateFromAppleIap(
                $company,
                $meta['plan'],
                $meta['billing'],
                $expiresAt,
            );

            return $row;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{transaction: AppleIapTransaction, company: Company}
     */
    private function applyTransactionPayload(User $user, Company $company, array $payload, string $rawJws): array
    {
        $productId = (string) ($payload['productId'] ?? '');
        $meta = AppleIapProducts::resolve($productId);
        if ($meta === null) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Produk langganan tidak dikenali.'],
            ]);
        }

        if (isset($payload['revocationDate']) && (int) $payload['revocationDate'] > 0) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Transaksi Apple telah dibatalkan.'],
            ]);
        }

        $transactionId = (string) ($payload['transactionId'] ?? '');
        $originalId = (string) ($payload['originalTransactionId'] ?? $transactionId);
        if ($transactionId === '') {
            throw ValidationException::withMessages([
                'signed_transaction' => ['ID transaksi Apple tidak ditemukan.'],
            ]);
        }

        $expiresAt = $this->expiresAtFromPayload($payload, $meta['billing']);
        if ($expiresAt && $expiresAt->isPast()) {
            throw ValidationException::withMessages([
                'signed_transaction' => ['Langganan Apple sudah berakhir.'],
            ]);
        }

        return DB::transaction(function () use ($user, $company, $payload, $productId, $meta, $transactionId, $originalId, $expiresAt) {
            $existing = AppleIapTransaction::query()->where('transaction_id', $transactionId)->first();
            if ($existing) {
                CompanySubscription::activateFromAppleIap(
                    $company,
                    $meta['plan'],
                    $meta['billing'],
                    $existing->expires_at ?? $expiresAt,
                );

                return [
                    'transaction' => $existing->fresh(),
                    'company' => $company->fresh(),
                ];
            }

            $row = AppleIapTransaction::query()->create([
                'transaction_id' => $transactionId,
                'original_transaction_id' => $originalId,
                'user_id' => $user->id,
                'company_id' => $company->id,
                'product_id' => $productId,
                'plan_key' => $meta['plan'],
                'billing' => $meta['billing'],
                'environment' => (string) ($payload['environment'] ?? null),
                'bundle_id' => (string) ($payload['bundleId'] ?? null),
                'purchased_at' => $this->msToCarbon($payload['purchaseDate'] ?? null),
                'expires_at' => $expiresAt,
                'raw_payload' => $payload,
            ]);

            CompanySubscription::activateFromAppleIap(
                $company,
                $meta['plan'],
                $meta['billing'],
                $expiresAt,
            );

            return [
                'transaction' => $row,
                'company' => $company->fresh(),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function expiresAtFromPayload(array $payload, string $billing): ?Carbon
    {
        $fromApple = $this->msToCarbon($payload['expiresDate'] ?? null);
        if ($fromApple) {
            return $fromApple;
        }

        $plan = PricingPlans::find(
            AppleIapProducts::resolve((string) ($payload['productId'] ?? ''))['plan'] ?? 'starter'
        );
        $pricing = PricingPlans::resolveBillingPrice($plan, $billing === 'annual' ? 'annual' : 'monthly');
        $months = max(1, (int) ($pricing['months'] ?? ($billing === 'annual' ? 12 : 1)));

        return now()->addMonthsNoOverflow($months)->endOfDay();
    }

    private function msToCarbon(mixed $ms): ?Carbon
    {
        if ($ms === null || $ms === '' || (int) $ms <= 0) {
            return null;
        }

        return Carbon::createFromTimestampMs((int) $ms);
    }
}
