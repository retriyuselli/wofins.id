<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubscriptionOrder extends Model
{
    protected $fillable = [
        'user_id',
        'order_code',
        'plan_key',
        'plan_name',
        'billing',
        'amount',
        'unique_amount',
        'full_name',
        'email',
        'phone',
        'company_name',
        'payment_proof_path',
        'notes',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'unique_amount' => 'integer',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $order): void {
            if (filled($order->payment_proof_path)) {
                Storage::disk('public')->delete($order->payment_proof_path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (! $this->payment_proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_proof_path);
    }

    public function getBillingLabelAttribute(): string
    {
        return \App\Support\PricingPlans::billingLabel((string) $this->billing);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format((int) $this->amount, 0, ',', '.');
    }

    public function getBaseAmountAttribute(): int
    {
        return max(0, (int) $this->amount - (int) $this->unique_amount);
    }

    public function getFormattedUniqueAmountAttribute(): string
    {
        return 'Rp '.number_format((int) $this->unique_amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_review' => 'Menunggu tinjauan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => $this->status,
        };
    }

    /**
     * Pesanan yang masih menunggu tinjauan untuk user (satu per waktu).
     */
    public static function pendingForUser(?User $user): ?self
    {
        if (! $user) {
            return null;
        }

        return static::query()
            ->where('status', 'pending_review')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest('submitted_at')
            ->latest('id')
            ->first();
    }

    /**
     * Pesanan siap untuk Approve user: sudah pilih paket + lampirkan bukti bayar.
     */
    public static function readyForUserApproval(?User $user): ?self
    {
        if (! $user) {
            return null;
        }

        return static::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->whereNotNull('plan_key')
            ->where('plan_key', '!=', '')
            ->whereNotNull('payment_proof_path')
            ->where('payment_proof_path', '!=', '')
            ->whereIn('status', ['pending_review', 'approved'])
            ->latest('submitted_at')
            ->latest('id')
            ->first();
    }
}
