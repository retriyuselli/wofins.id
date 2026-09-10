<?php

namespace App\Models;

use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PaymentMethod extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'bank_name',
        'no_rekening',
        'is_cash',
        'cabang',
        'opening_balance',
        'opening_balance_date',
    ];

    protected $casts = [
        'opening_balance' => 'integer',
        'opening_balance_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_company', function (Builder $builder) {
            if (! Schema::hasColumn('payment_methods', 'company_id')) {
                return;
            }

            // Super admin: semua company. Lainnya: hanya rekening company sendiri.
            // Tanpa auth / tanpa company → kosong (jangan return tanpa filter).
            if (ProFeatures::actorIsSuperAdmin()) {
                return;
            }

            $companyId = UserVisibility::companyId();
            $table = $builder->getModel()->getTable();

            if ($companyId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where("{$table}.company_id", $companyId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'bank_name', 'no_rekening', 'is_cash', 'company_id'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('payment_method');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function orders(): HasMany
    {
        return $this->isolateHasManyToCompany($this->hasMany(Order::class));
    }

    public function payments(): HasMany
    {
        return $this->isolateHasManyToCompany($this->hasMany(DataPembayaran::class));
    }

    public function expenses(): HasMany
    {
        return $this->isolateHasManyToCompany($this->hasMany(Expense::class, 'payment_method_id'));
    }

    public function expenseOps(): HasMany
    {
        return $this->isolateHasManyToCompany($this->hasMany(ExpenseOps::class, 'payment_method_id'));
    }

    public function pendapatanLains(): HasMany
    {
        return $this->isolateHasManyToCompany($this->hasMany(PendapatanLain::class, 'payment_method_id'));
    }

    public function pengeluaranLains(): HasMany
    {
        return $this->isolateHasManyToCompany($this->hasMany(PengeluaranLain::class, 'payment_method_id'));
    }

    /**
     * Mutasi rekening hanya dari company yang sama dengan rekening.
     */
    private function isolateHasManyToCompany(HasMany $relation): HasMany
    {
        if (! $this->company_id) {
            return $relation;
        }

        $table = $relation->getRelated()->getTable();

        if (! Schema::hasColumn($table, 'company_id')) {
            return $relation;
        }

        return $relation->where($table.'.company_id', $this->company_id);
    }

    /**
     * Tanggal mulai hitung mutasi (Y-m-d), atau null = semua transaksi.
     *
     * Tanggal pembukuan hanya memotong mutasi jika saldo awal > 0
     * (saldo awal sudah mewakili posisi kas sebelum tanggal itu).
     * Jika saldo awal 0 / tanggal kosong, semua pembayaran harus masuk ke saldo —
     * kalau tidak, widget periode menampilkan Masuk 5jt sementara saldo tetap 0.
     */
    public function transactionCutoffDate(): ?string
    {
        if ((int) $this->opening_balance === 0 || ! $this->opening_balance_date) {
            return null;
        }

        return $this->opening_balance_date->toDateString();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function applyOpeningCutoff($query, string $dateColumn)
    {
        $cutoff = $this->transactionCutoffDate();

        if ($cutoff) {
            $query->whereDate($dateColumn, '>=', $cutoff);
        }

        return $query;
    }

    /**
     * Hitung saldo akhir rekening:
     * Saldo Akhir = Saldo Awal + Total Uang Masuk - Total Uang Keluar
     */
    public function getSaldoAttribute(): float
    {
        return (float) $this->opening_balance
            + $this->getTotalUangMasuk()
            - $this->getTotalUangKeluar();
    }

    /**
     * Hitung total uang masuk dari semua sumber
     */
    public function getTotalUangMasuk($startDate = null): float
    {
        $cutoff = $startDate !== null
            ? $this->normalizeCutoffDate($startDate)
            : $this->transactionCutoffDate();

        $totalMasukWedding = $this->payments()
            ->when($cutoff, fn ($query) => $query->whereDate('tgl_bayar', '>=', $cutoff))
            ->whereNull('deleted_at')
            ->sum('nominal') ?? 0;

        $totalMasukLain = $this->pendapatanLains()
            ->when($cutoff, fn ($query) => $query->whereDate('tgl_bayar', '>=', $cutoff))
            ->whereNull('deleted_at')
            ->sum('nominal') ?? 0;

        return (float) ($totalMasukWedding + $totalMasukLain);
    }

    /**
     * Hitung total uang keluar dari semua sumber
     */
    public function getTotalUangKeluar($startDate = null): float
    {
        $cutoff = $startDate !== null
            ? $this->normalizeCutoffDate($startDate)
            : $this->transactionCutoffDate();

        $totalKeluarWedding = $this->expenses()
            ->when($cutoff, fn ($query) => $query->whereDate('date_expense', '>=', $cutoff))
            ->whereNull('deleted_at')
            ->sum('amount') ?? 0;

        $totalKeluarOps = $this->expenseOps()
            ->when($cutoff, fn ($query) => $query->whereDate('date_expense', '>=', $cutoff))
            ->whereNull('deleted_at')
            ->sum('amount') ?? 0;

        $totalKeluarLain = $this->pengeluaranLains()
            ->when($cutoff, fn ($query) => $query->whereDate('date_expense', '>=', $cutoff))
            ->whereNull('deleted_at')
            ->sum('amount') ?? 0;

        return (float) ($totalKeluarWedding + $totalKeluarOps + $totalKeluarLain);
    }

    private function normalizeCutoffDate(mixed $startDate): ?string
    {
        if ($startDate instanceof \DateTimeInterface) {
            return $startDate->format('Y-m-d');
        }

        if (is_string($startDate) && $startDate !== '') {
            return $startDate;
        }

        return null;
    }

    /**
     * Hitung perubahan saldo (naik/turun) dari saldo awal
     */
    public function getPerubahanSaldoAttribute(): float
    {
        return $this->saldo - $this->opening_balance;
    }

    /**
     * Status perubahan saldo (positif/negatif)
     */
    public function getStatusPerubahanAttribute(): string
    {
        $perubahan = $this->perubahan_saldo;

        if ($perubahan > 0) {
            return 'naik';
        } elseif ($perubahan < 0) {
            return 'turun';
        } else {
            return 'tetap';
        }
    }

    /**
     * Breakdown detail saldo untuk debugging
     */
    public function getSaldoBreakdown(): array
    {
        $cutoff = $this->transactionCutoffDate();

        return [
            'saldo_awal' => $this->opening_balance,
            'tanggal_pembukuan' => $cutoff,
            'uang_masuk' => [
                'wedding' => $this->payments()
                    ->when($cutoff, fn ($query) => $query->whereDate('tgl_bayar', '>=', $cutoff))
                    ->whereNull('deleted_at')
                    ->sum('nominal') ?? 0,
                'lainnya' => $this->pendapatanLains()
                    ->when($cutoff, fn ($query) => $query->whereDate('tgl_bayar', '>=', $cutoff))
                    ->whereNull('deleted_at')
                    ->sum('nominal') ?? 0,
                'total' => $this->getTotalUangMasuk(),
            ],
            'uang_keluar' => [
                'wedding' => $this->expenses()
                    ->when($cutoff, fn ($query) => $query->whereDate('date_expense', '>=', $cutoff))
                    ->whereNull('deleted_at')
                    ->sum('amount') ?? 0,
                'operasional' => $this->expenseOps()
                    ->when($cutoff, fn ($query) => $query->whereDate('date_expense', '>=', $cutoff))
                    ->whereNull('deleted_at')
                    ->sum('amount') ?? 0,
                'lainnya' => $this->pengeluaranLains()
                    ->when($cutoff, fn ($query) => $query->whereDate('date_expense', '>=', $cutoff))
                    ->whereNull('deleted_at')
                    ->sum('amount') ?? 0,
                'total' => $this->getTotalUangKeluar(),
            ],
            'saldo_akhir' => $this->saldo,
            'perubahan' => $this->perubahan_saldo,
            'status' => $this->status_perubahan,
        ];
    }
}
