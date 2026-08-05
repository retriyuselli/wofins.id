<?php

namespace App\Services;

use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\Order;
use App\Models\Piutang;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\User;
use App\Enums\StatusPiutang;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
class FinanceSummaryService
{
    /**
     * Roles that can see all projects / company-wide finance.
     */
    public function isPrivileged(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'Finance', 'admin_am']);
    }

    /**
     * @return array{from: string, to: string}
     */
    public function resolvePeriod(?string $from, ?string $to): array
    {
        $end = $to ? Carbon::parse($to)->startOfDay() : now()->startOfDay();
        $start = $from ? Carbon::parse($from)->startOfDay() : $end->copy()->startOfMonth();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ];
    }

    /**
     * Cash dashboard using business dates (tgl_bayar / date_expense).
     *
     * @return array<string, mixed>
     */
    public function dashboard(string $from, string $to): array
    {
        $inflow = $this->cashInflow($from, $to);
        $outflow = $this->cashOutflow($from, $to);

        $prevEnd = Carbon::parse($from)->subDay();
        $prevStart = $prevEnd->copy()->subDays(
            Carbon::parse($from)->diffInDays(Carbon::parse($to))
        );
        $prevFrom = $prevStart->toDateString();
        $prevTo = $prevEnd->toDateString();

        $prevIn = $this->cashInflow($prevFrom, $prevTo);
        $prevOut = $this->cashOutflow($prevFrom, $prevTo);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'inflow' => $inflow,
            'outflow' => $outflow,
            'net_cash' => $inflow['total'] - $outflow['total'],
            'comparison' => [
                'period' => ['from' => $prevFrom, 'to' => $prevTo],
                'previous_inflow' => $prevIn['total'],
                'previous_outflow' => $prevOut['total'],
                'previous_net_cash' => $prevIn['total'] - $prevOut['total'],
            ],
        ];
    }

    /**
     * @return array{wedding_payments: int, other_income: int, total: int}
     */
    public function cashInflow(string $from, string $to): array
    {
        $wedding = (int) DataPembayaran::query()
            ->whereBetween('tgl_bayar', [$from, $to])
            ->sum('nominal');

        $other = (int) PendapatanLain::query()
            ->whereBetween('tgl_bayar', [$from, $to])
            ->sum('nominal');

        return [
            'wedding_payments' => $wedding,
            'other_income' => $other,
            'total' => $wedding + $other,
        ];
    }

    /**
     * @return array{wedding_expenses: int, operational: int, other_expenses: int, total: int}
     */
    public function cashOutflow(string $from, string $to): array
    {
        $wedding = (int) Expense::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        $ops = (int) ExpenseOps::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        $other = (int) PengeluaranLain::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        return [
            'wedding_expenses' => $wedding,
            'operational' => $ops,
            'other_expenses' => $other,
            'total' => $wedding + $ops + $other,
        ];
    }

    public function scopedOrdersQuery(User $user): Builder
    {
        $query = Order::query()->with([
            'prospect:id,name_event,date_lamaran,date_akad,date_resepsi',
            'user:id,name',
            'dataPembayaran:id,order_id,nominal,tgl_bayar,keterangan,payment_method_id',
            'dataPengeluaran:id,order_id,amount,date_expense,note,vendor_id,payment_stage',
            'expenses:id,order_id,amount,date_expense,note,vendor_id,payment_stage',
        ]);

        if (! $this->isPrivileged($user)) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|float>}
     */
    public function projects(User $user, ?string $status = null, int $perPage = 20): array
    {
        $query = $this->scopedOrdersQuery($user)->latest('id');

        if ($status) {
            $query->where('status', $status);
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(min(max($perPage, 1), 50));

        $data = collect($paginator->items())->map(fn (Order $order) => $this->projectSummary($order))->values()->all();

        $metaTotals = [
            'total_grand_total' => 0,
            'total_payments' => 0,
            'total_expenses' => 0,
            'total_net_cash_flow' => 0,
        ];

        foreach ($data as $row) {
            $metaTotals['total_grand_total'] += $row['grand_total'];
            $metaTotals['total_payments'] += $row['paid_amount'];
            $metaTotals['total_expenses'] += $row['expenses_total'];
            $metaTotals['total_net_cash_flow'] += $row['net_cash_flow'];
        }

        return [
            'data' => $data,
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $metaTotals),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function projectDetail(User $user, int $id): ?array
    {
        /** @var Order|null $order */
        $order = $this->scopedOrdersQuery($user)
            ->with([
                'dataPembayaran.paymentMethod:id,name,no_rekening',
                'expenses.vendor:id,name',
            ])
            ->find($id);

        if (! $order) {
            return null;
        }

        $summary = $this->projectSummary($order);
        $finance = OrderFinance::for($order);

        return array_merge($summary, [
            'totals' => [
                'grand_total' => $finance->grandTotal(),
                'paid' => $finance->paymentsTotal(),
                'remaining' => $finance->sisa(),
                'expenses' => $finance->expensesTotal(),
                'net_cash' => $finance->uangDiterima(),
                'gross_profit' => $finance->labaKotor(),
            ],
            'payments' => $order->dataPembayaran->map(function (DataPembayaran $p) {
                return [
                    'id' => $p->id,
                    'date' => optional($p->tgl_bayar)?->toDateString() ?? (string) $p->tgl_bayar,
                    'amount' => (int) $p->nominal,
                    'keterangan' => $p->keterangan,
                    'payment_method' => $this->formatPaymentMethod($p->paymentMethod),
                ];
            })->values()->all(),
            'expenses' => $order->expenses->map(function (Expense $e) {
                return [
                    'id' => $e->id,
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'amount' => (int) $e->amount,
                    'note' => $e->note,
                    'vendor' => $e->vendor?->name,
                    'payment_stage' => $e->payment_stage,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function projectSummary(Order $order): array
    {
        $finance = OrderFinance::for($order);
        $paid = $finance->paymentsTotal();
        $expenses = $finance->expensesTotal();
        $grand = $finance->grandTotal();
        $prospect = $order->prospect;

        $status = $order->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        return [
            'id' => $order->id,
            'slug' => $order->slug,
            'name' => $order->name ?: ($prospect?->name_event),
            'number' => $order->number,
            'status' => $statusValue,
            'closing_date' => optional($order->closing_date)?->toDateString(),
            'account_manager' => $order->user?->name,
            'prospect' => $prospect ? [
                'id' => $prospect->id,
                'name_event' => $prospect->name_event,
                'date_lamaran' => optional($prospect->date_lamaran)?->toDateString(),
                'date_akad' => optional($prospect->date_akad)?->toDateString(),
                'date_resepsi' => optional($prospect->date_resepsi)?->toDateString(),
            ] : null,
            'grand_total' => $grand,
            'paid_amount' => $paid,
            'remaining' => $grand - $paid,
            'expenses_total' => $expenses,
            'net_cash_flow' => $paid - $expenses,
            'gross_profit' => $grand - $expenses,
        ];
    }

    /**
     * Unified cash ledger (same sources as LaporanKeuangan).
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function transactions(string $from, string $to, ?string $type = null, int $limit = 100, ?string $direction = null): array
    {
        $rows = $this->cashLedgerRows($from, $to);

        if ($type) {
            $rows = $rows->filter(fn (array $r) => $r['type'] === $type)->values();
        }

        if ($direction) {
            $rows = $rows->filter(fn (array $r) => $r['direction'] === $direction)->values();
        }

        $totalIn = (int) $rows->where('direction', 'in')->sum('amount');
        $totalOut = (int) $rows->where('direction', 'out')->sum('amount');

        $sorted = $rows->sortBy('date')->values();
        $running = 0;
        $withBalance = $sorted->map(function (array $row) use (&$running) {
            $running += $row['direction'] === 'in' ? $row['amount'] : -$row['amount'];
            $row['running_balance'] = $running;

            return $row;
        });

        // Newest first for mobile list; keep running_balance computed oldest→newest.
        $data = $withBalance->sortByDesc('date')->take($limit)->values()->all();

        return [
            'data' => $data,
            'meta' => [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net' => $totalIn - $totalOut,
                'count' => count($data),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportSummary(string $from, string $to, string $mode = 'cash'): array
    {
        if ($mode === 'profit_loss') {
            return $this->profitLossSummary($from, $to);
        }

        $in = $this->cashInflow($from, $to);
        $out = $this->cashOutflow($from, $to);

        return [
            'mode' => 'cash',
            'period' => ['from' => $from, 'to' => $to],
            'by_type' => [
                'Masuk (Wedding)' => $in['wedding_payments'],
                'Masuk (Lain-lain)' => $in['other_income'],
                'Keluar (Wedding)' => $out['wedding_expenses'],
                'Keluar (Operasional)' => $out['operational'],
                'Keluar (Lain-lain)' => $out['other_expenses'],
            ],
            'total_in' => $in['total'],
            'total_out' => $out['total'],
            'net' => $in['total'] - $out['total'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profitLossSummary(string $from, string $to): array
    {
        $orders = Order::query()
            ->with(['dataPembayaran', 'expenses', 'prospect'])
            ->whereHas('prospect', function (Builder $q) use ($from, $to) {
                $q->where(function (Builder $inner) use ($from, $to) {
                    $inner->whereBetween('date_lamaran', [$from, $to])
                        ->orWhereBetween('date_akad', [$from, $to])
                        ->orWhereBetween('date_resepsi', [$from, $to]);
                });
            })
            ->get();

        $totalOrderValue = 0;
        $totalPayments = 0;
        $totalExpenses = 0;

        foreach ($orders as $order) {
            $finance = OrderFinance::for($order);
            $totalOrderValue += $finance->grandTotal();
            $totalPayments += $finance->paymentsTotal();
            $totalExpenses += $finance->expensesTotal();
        }

        $ops = (int) ExpenseOps::query()->whereBetween('date_expense', [$from, $to])->sum('amount');
        $otherExp = (int) PengeluaranLain::query()->whereBetween('date_expense', [$from, $to])->sum('amount');
        $otherInc = (int) PendapatanLain::query()->whereBetween('tgl_bayar', [$from, $to])->sum('nominal');

        return [
            'mode' => 'profit_loss',
            'period' => ['from' => $from, 'to' => $to],
            'orders_count' => $orders->count(),
            'total_order_value' => $totalOrderValue,
            'total_payments_on_orders' => $totalPayments,
            'total_wedding_expenses' => $totalExpenses,
            'net_profit' => $totalOrderValue - $totalExpenses,
            'operational_expenses' => $ops,
            'other_expenses' => $otherExp,
            'other_income' => $otherInc,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function cashLedgerRows(string $from, string $to): Collection
    {
        $weddingIn = DataPembayaran::query()
            ->with(['order:id,name,prospect_id', 'order.prospect:id,name_event', 'paymentMethod:id,name,no_rekening'])
            ->whereBetween('tgl_bayar', [$from, $to])
            ->get()
            ->map(function (DataPembayaran $p) {
                return [
                    'date' => optional($p->tgl_bayar)?->toDateString() ?? (string) $p->tgl_bayar,
                    'type' => 'wedding_payment',
                    'direction' => 'in',
                    'amount' => (int) $p->nominal,
                    'description' => $p->keterangan,
                    'order_id' => $p->order_id,
                    'prospect_name' => $p->order?->prospect?->name_event ?? $p->order?->name,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($p->paymentMethod),
                    'source_table' => 'data_pembayarans',
                    'source_id' => $p->id,
                ];
            });

        $otherIn = PendapatanLain::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('tgl_bayar', [$from, $to])
            ->get()
            ->map(function (PendapatanLain $p) {
                return [
                    'date' => optional($p->tgl_bayar)?->toDateString() ?? (string) $p->tgl_bayar,
                    'type' => 'other_income',
                    'direction' => 'in',
                    'amount' => (int) $p->nominal,
                    'description' => $p->keterangan ?? $p->name,
                    'order_id' => null,
                    'prospect_name' => null,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($p->paymentMethod),
                    'source_table' => 'pendapatan_lains',
                    'source_id' => $p->id,
                ];
            });

        $weddingOut = Expense::query()
            ->with(['order:id,name,prospect_id', 'order.prospect:id,name_event', 'vendor:id,name', 'paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$from, $to])
            ->get()
            ->map(function (Expense $e) {
                return [
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'type' => 'wedding_expense',
                    'direction' => 'out',
                    'amount' => (int) $e->amount,
                    'description' => $e->note,
                    'order_id' => $e->order_id,
                    'prospect_name' => $e->order?->prospect?->name_event ?? $e->order?->name,
                    'vendor_name' => $e->vendor?->name,
                    'payment_method' => $this->formatPaymentMethod($e->paymentMethod),
                    'source_table' => 'expenses',
                    'source_id' => $e->id,
                ];
            });

        $opsOut = ExpenseOps::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$from, $to])
            ->get()
            ->map(function (ExpenseOps $e) {
                return [
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'type' => 'operational_expense',
                    'direction' => 'out',
                    'amount' => (int) $e->amount,
                    'description' => $e->note ?? $e->name,
                    'order_id' => null,
                    'prospect_name' => null,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($e->paymentMethod),
                    'source_table' => 'expense_ops',
                    'source_id' => $e->id,
                ];
            });

        $otherOut = PengeluaranLain::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$from, $to])
            ->get()
            ->map(function (PengeluaranLain $e) {
                return [
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'type' => 'other_expense',
                    'direction' => 'out',
                    'amount' => (int) $e->amount,
                    'description' => $e->note ?? $e->name,
                    'order_id' => null,
                    'prospect_name' => null,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($e->paymentMethod),
                    'source_table' => 'pengeluaran_lains',
                    'source_id' => $e->id,
                ];
            });

        return $weddingIn
            ->concat($otherIn)
            ->concat($weddingOut)
            ->concat($opsOut)
            ->concat($otherOut)
            ->values();
    }

    protected function formatPaymentMethod(?\App\Models\PaymentMethod $method): ?string
    {
        if (! $method) {
            return null;
        }

        $label = trim((string) ($method->name ?? ''));
        $account = trim((string) ($method->no_rekening ?? ''));

        if ($account !== '') {
            $label .= ($label !== '' ? ' (' : '(').$account.')';
        }

        return $label !== '' ? $label : null;
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function piutangs(?string $status = null, int $perPage = 20, bool $openOnly = false): array
    {
        $query = Piutang::query()->latest('tanggal_piutang')->latest('id');

        $openStatuses = [
            StatusPiutang::AKTIF->value,
            StatusPiutang::DIBAYAR_SEBAGIAN->value,
            StatusPiutang::JATUH_TEMPO->value,
        ];

        if ($status) {
            $query->where('status', $status);
        } elseif ($openOnly) {
            $query->whereIn('status', $openStatuses);
        }

        $paginator = $query->paginate(min(max($perPage, 1), 50));

        $data = collect($paginator->items())->map(fn (Piutang $p) => $this->piutangSummary($p))->values()->all();

        $metaOpen = Piutang::query()->whereIn('status', $openStatuses);

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'open_count' => (int) (clone $metaOpen)->count(),
                'open_sisa' => (int) (clone $metaOpen)->sum('sisa_piutang'),
                'open_total' => (int) (clone $metaOpen)->sum('total_piutang'),
                'open_paid' => (int) (clone $metaOpen)->sum('sudah_dibayar'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function piutangDetail(int $id): ?array
    {
        /** @var Piutang|null $piutang */
        $piutang = Piutang::query()
            ->with([
                'pembayaranPiutangs' => fn ($q) => $q->latest('tanggal_pembayaran')->latest('id'),
                'pembayaranPiutangs.paymentMethod:id,name,no_rekening',
                'dibuatOleh:id,name',
            ])
            ->find($id);

        if (! $piutang) {
            return null;
        }

        $summary = $this->piutangSummary($piutang);

        return array_merge($summary, [
            'catatan' => $piutang->catatan,
            'keterangan' => $piutang->keterangan,
            'kontak_debitur' => $piutang->kontak_debitur,
            'dibuat_oleh' => $piutang->dibuatOleh?->name,
            'payments' => $piutang->pembayaranPiutangs->map(function ($bayar) {
                return [
                    'id' => $bayar->id,
                    'nomor' => $bayar->nomor_pembayaran,
                    'date' => optional($bayar->tanggal_pembayaran)?->toDateString(),
                    'amount' => (int) $bayar->jumlah_pembayaran,
                    'bunga' => (int) ($bayar->jumlah_bunga ?? 0),
                    'denda' => (int) ($bayar->denda ?? 0),
                    'total' => (int) $bayar->total_pembayaran,
                    'payment_method' => $this->formatPaymentMethod($bayar->paymentMethod),
                    'catatan' => $bayar->catatan,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function piutangSummary(Piutang $piutang): array
    {
        $status = $piutang->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
        $jenis = $piutang->jenis_piutang;
        $jenisValue = $jenis instanceof \BackedEnum ? $jenis->value : (string) $jenis;

        return [
            'id' => $piutang->id,
            'nomor' => $piutang->nomor_piutang,
            'nama_debitur' => $piutang->nama_debitur,
            'jenis' => $jenisValue,
            'status' => $statusValue,
            'status_label' => $status instanceof StatusPiutang ? $status->getLabel() : $statusValue,
            'prioritas' => $piutang->prioritas,
            'jumlah_pokok' => (int) $piutang->jumlah_pokok,
            'total_piutang' => (int) $piutang->total_piutang,
            'sudah_dibayar' => (int) $piutang->sudah_dibayar,
            'sisa_piutang' => (int) $piutang->sisa_piutang,
            'tanggal_piutang' => optional($piutang->tanggal_piutang)?->toDateString(),
            'tanggal_jatuh_tempo' => optional($piutang->tanggal_jatuh_tempo)?->toDateString(),
            'tanggal_lunas' => optional($piutang->tanggal_lunas)?->toDateString(),
            'is_overdue' => $piutang->tanggal_jatuh_tempo
                && $piutang->tanggal_jatuh_tempo->isPast()
                && ! in_array($statusValue, [StatusPiutang::LUNAS->value, StatusPiutang::DIBATALKAN->value], true),
        ];
    }
}
