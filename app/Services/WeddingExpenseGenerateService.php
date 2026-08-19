<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\NotaDinasDetail;
use App\Models\PaymentMethod;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WeddingExpenseGenerateService
{
    /**
     * Buat pengeluaran wedding dari Nota Dinas Detail yang belum masuk expenses.
     * Satu detail → satu expense, diikat ke order yang sama.
     *
     * @return array{
     *     created: int,
     *     skipped_existing: int,
     *     skipped_invalid: int,
     *     skipped_quota: int,
     *     pending: int
     * }
     */
    public function generate(int $paymentMethodId, ?int $orderId = null): array
    {
        Gate::authorize('create', Expense::class);

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

        $remaining = CompanySubscription::remaining(CompanySubscription::RESOURCE_EXPENSES);

        DB::transaction(function () use (
            $orderId,
            $paymentMethod,
            $remaining,
            &$created,
            &$skippedExisting,
            &$skippedInvalid,
            &$skippedQuota,
            &$pendingCount,
        ): void {
            $pending = $this->pendingQuery($orderId)
                ->with(['notaDinas', 'vendor'])
                ->orderBy('order_id')
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

                Expense::query()->create($this->payload($detail, (int) $paymentMethod->id));
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

    public function pendingCount(?int $orderId = null): int
    {
        return $this->pendingQuery($orderId)->count();
    }

    /**
     * @return Builder<NotaDinasDetail>
     */
    public function pendingQuery(?int $orderId = null): Builder
    {
        $query = UserVisibility::constrainNotaDinasDetailsQuery(
            NotaDinasDetail::query()
        )
            ->where('jenis_pengeluaran', 'wedding')
            ->whereNotNull('order_id')
            ->whereNotNull('vendor_id');

        $linkedIds = $this->linkedDetailIdList();
        if ($linkedIds !== []) {
            $query->whereNotIn('id', $linkedIds);
        }

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query;
    }

    /**
     * ID Nota Dinas Detail yang sudah punya pengeluaran (termasuk yang di-soft-delete).
     *
     * @return array<int, true>
     */
    private function linkedDetailIds(): array
    {
        $ids = Expense::withTrashed()
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
        return filled($detail->order_id)
            && filled($detail->vendor_id)
            && (string) $detail->jenis_pengeluaran === 'wedding';
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(NotaDinasDetail $detail, int $paymentMethodId): array
    {
        $notaDinas = $detail->notaDinas;
        $noteParts = array_filter([
            $detail->keperluan,
            $detail->event,
            $detail->payment_stage,
        ], fn ($part) => filled($part));

        return [
            'order_id' => $detail->order_id,
            'vendor_id' => $detail->vendor_id,
            'payment_method_id' => $paymentMethodId,
            'nota_dinas_id' => $detail->nota_dinas_id,
            'nota_dinas_detail_id' => $detail->id,
            'note' => $noteParts !== [] ? implode(' - ', $noteParts) : 'Pengeluaran wedding',
            'date_expense' => $notaDinas?->tanggal
                ?? $detail->created_at?->toDateString()
                ?? now()->toDateString(),
            'amount' => (int) $detail->jumlah_transfer,
            'no_nd' => $notaDinas?->no_nd,
            'kategori_transaksi' => 'uang_keluar',
            'payment_stage' => $detail->payment_stage,
            'account_holder' => $detail->account_holder,
            'bank_name' => $detail->bank_name,
            'bank_account' => $detail->bank_account,
            'image' => $detail->invoice_file,
        ];
    }
}
