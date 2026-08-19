<?php

namespace App\Services;

use App\Enums\PengeluaranJenis;
use App\Models\ExpenseOps;
use App\Models\NotaDinasDetail;
use App\Models\PaymentMethod;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExpenseOpsGenerateService
{
    /**
     * Buat pengeluaran operasional dari Nota Dinas Detail yang belum masuk expense_ops.
     *
     * @return array{
     *     created: int,
     *     skipped_existing: int,
     *     skipped_invalid: int,
     *     skipped_quota: int,
     *     pending: int
     * }
     */
    public function generate(int $paymentMethodId, ?int $notaDinasId = null): array
    {
        Gate::authorize('create', ExpenseOps::class);

        $paymentMethod = PaymentMethod::query()->find($paymentMethodId);
        if (! $paymentMethod) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Metode pembayaran tidak valid.',
            ]);
        }

        $created = 0;
        $skippedExisting = 0;
        $skippedInvalid = 0;
        $skippedQuota = 0;
        $pendingCount = 0;

        $remaining = CompanySubscription::remaining(CompanySubscription::RESOURCE_EXPENSE_OPS);

        DB::transaction(function () use (
            $notaDinasId,
            $paymentMethod,
            $remaining,
            &$created,
            &$skippedExisting,
            &$skippedInvalid,
            &$skippedQuota,
            &$pendingCount,
        ): void {
            $pending = $this->pendingQuery($notaDinasId)
                ->with(['notaDinas', 'vendor'])
                ->orderBy('nota_dinas_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $pendingCount = $pending->count();
            $alreadyLinked = $this->linkedDetailIds();

            foreach ($pending as $detail) {
                if (! $this->isEligible($detail)) {
                    $skippedInvalid++;

                    continue;
                }

                if (isset($alreadyLinked[(int) $detail->id])) {
                    $skippedExisting++;

                    continue;
                }

                if ($remaining !== null && $created >= $remaining) {
                    $skippedQuota++;

                    continue;
                }

                ExpenseOps::query()->create($this->payload($detail, (int) $paymentMethod->id));
                $alreadyLinked[(int) $detail->id] = true;
                $created++;
            }
        });

        return [
            'created' => $created,
            'skipped_existing' => $skippedExisting,
            'skipped_invalid' => $skippedInvalid,
            'skipped_quota' => $skippedQuota,
            'pending' => $pendingCount,
        ];
    }

    public function pendingCount(?int $notaDinasId = null): int
    {
        return $this->pendingQuery($notaDinasId)->count();
    }

    /**
     * @return Builder<NotaDinasDetail>
     */
    public function pendingQuery(?int $notaDinasId = null): Builder
    {
        $query = UserVisibility::constrainNotaDinasDetailsQuery(
            NotaDinasDetail::query()
        )
            ->where('jenis_pengeluaran', PengeluaranJenis::OPERASIONAL->value)
            ->whereNotNull('vendor_id');

        $linkedIds = $this->linkedDetailIdList();
        if ($linkedIds !== []) {
            $query->whereNotIn('id', $linkedIds);
        }

        if ($notaDinasId) {
            $query->where('nota_dinas_id', $notaDinasId);
        }

        return $query;
    }

    /**
     * @return array<int, true>
     */
    private function linkedDetailIds(): array
    {
        $ids = ExpenseOps::withTrashed()
            ->whereNotNull('nota_dinas_detail_id')
            ->pluck('nota_dinas_detail_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        return array_fill_keys($ids, true);
    }

    /**
     * @return list<int>
     */
    private function linkedDetailIdList(): array
    {
        return array_keys($this->linkedDetailIds());
    }

    private function isEligible(NotaDinasDetail $detail): bool
    {
        return filled($detail->vendor_id)
            && (string) $detail->jenis_pengeluaran === PengeluaranJenis::OPERASIONAL->value;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(NotaDinasDetail $detail, int $paymentMethodId): array
    {
        $notaDinas = $detail->notaDinas;
        $vendor = $detail->vendor;

        $nameParts = array_filter([
            $detail->keperluan,
            ($detail->event && $detail->event !== $detail->keperluan) ? $detail->event : null,
        ], fn ($part) => filled($part));

        $expenseDate = $notaDinas?->tanggal?->toDateString()
            ?? $detail->created_at?->toDateString()
            ?? now()->toDateString();

        return [
            'name' => $nameParts !== [] ? implode(' - ', $nameParts) : 'Pengeluaran Operasional',
            'amount' => (int) $detail->jumlah_transfer,
            'payment_method_id' => $paymentMethodId,
            'date_expense' => $expenseDate,
            'tanggal_transfer' => $expenseDate,
            'image' => $detail->invoice_file,
            'no_nd' => $notaDinas?->no_nd,
            'note' => $detail->keperluan ?: 'Pengeluaran operasional',
            'kategori_transaksi' => 'uang_keluar',
            'nota_dinas_id' => $detail->nota_dinas_id,
            'nota_dinas_detail_id' => $detail->id,
            'vendor_id' => $detail->vendor_id,
            'bank_name' => $detail->bank_name ?: $vendor?->bank_name,
            'account_holder' => $detail->account_holder ?: $vendor?->account_holder,
            'bank_account' => $detail->bank_account ?: $vendor?->bank_account,
        ];
    }
}
