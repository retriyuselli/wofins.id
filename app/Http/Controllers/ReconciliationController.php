<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliationItem;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PaymentMethod;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Services\ReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ReconciliationController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const SOURCE_MODELS = [
        'data_pembayarans' => DataPembayaran::class,
        'pendapatan_lains' => PendapatanLain::class,
        'expenses' => Expense::class,
        'expense_ops' => ExpenseOps::class,
        'pengeluaran_lains' => PengeluaranLain::class,
    ];

    protected $reconciliationService;

    public function __construct()
    {
        $this->reconciliationService = new ReconciliationService;
    }

    /**
     * Download Reconciliation Report as PDF
     */
    public function downloadPdf(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        try {
            $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
            Gate::authorize('view', $paymentMethod);

            $results = $this->reconciliationService->reconcile(
                $request->payment_method_id,
                $request->start_date,
                $request->end_date
            );

            // Get matched data with bank items and app transactions
            $matched = $results['matched'];
            $unmatchedApp = $results['unmatched_app'];
            $unmatchedBank = $results['unmatched_bank'];
            $statistics = $results['statistics'];

            $pdf = Pdf::loadView('pdf.reconciliation-report', [
                'paymentMethod' => $paymentMethod,
                'startDate' => $request->start_date,
                'endDate' => $request->end_date,
                'matched' => $matched,
                'unmatchedApp' => $unmatchedApp,
                'unmatchedBank' => $unmatchedBank,
                'statistics' => $statistics,
                'timestamp' => now()->format('d F Y H:i:s'),
                'user' => Auth::check() ? Auth::user()->name : 'System',
            ])->setPaper('a4', 'landscape');

            $filename = 'Reconciliation_Report_'.str_replace([' ', '/'], '_', $paymentMethod->no_rekening).'_'.$request->start_date.'.pdf';

            return $pdf->download($filename);

        } catch (AuthorizationException|ModelNotFoundException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return back()->with('error', 'Gagal generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Mark individual transaction as matched (manual match from UI)
     */
    public function markMatched(Request $request)
    {
        $request->validate([
            'source_id' => 'required|integer',
            'source_table' => ['required', 'string', Rule::in(array_keys(self::SOURCE_MODELS))],
            'bank_item_id' => 'required|integer',
            'confidence' => 'required|numeric',
        ]);

        try {
            $bankItem = $this->authorizedBankItem((int) $request->bank_item_id);
            $source = $this->sourceRecord(
                (string) $request->source_table,
                (int) $request->source_id,
                (int) $bankItem->bankStatement->payment_method_id,
            );

            $source->update([
                'reconciliation_status' => 'matched',
                'matched_bank_item_id' => $bankItem->id,
                'match_confidence' => $request->confidence,
                'reconciliation_notes' => 'Manually matched at '.now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil ditandai sebagai cocok',
            ]);

        } catch (AuthorizationException|ModelNotFoundException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai transaksi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto match high confidence transactions
     */
    public function autoMatch(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        try {
            $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
            Gate::authorize('update', $paymentMethod);

            $results = $this->reconciliationService->reconcile(
                $request->payment_method_id,
                $request->start_date,
                $request->end_date
            );

            $matchedCount = 0;

            foreach ($results['matched'] as $match) {
                if ($match['confidence'] >= ReconciliationService::HIGH_CONFIDENCE) {
                    $this->reconciliationService->saveMatch(
                        $match['app_transaction'],
                        $match['bank_item'],
                        $match['confidence'],
                        $match['match_criteria']
                    );
                    $matchedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'matched_count' => $matchedCount,
                'message' => "$matchedCount transaksi berhasil di-match otomatis",
            ]);

        } catch (AuthorizationException|ModelNotFoundException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan auto match: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unmark matched transaction
     */
    public function unmarkMatched(Request $request)
    {
        $request->validate([
            'source_id' => 'required|integer',
            'source_table' => ['required', 'string', Rule::in(array_keys(self::SOURCE_MODELS))],
            'bank_item_id' => 'required|integer',
        ]);

        try {
            $bankItem = $this->authorizedBankItem((int) $request->bank_item_id);
            $source = $this->sourceRecord(
                (string) $request->source_table,
                (int) $request->source_id,
                (int) $bankItem->bankStatement->payment_method_id,
            );

            $source->update([
                'reconciliation_status' => 'unmatched',
                'matched_bank_item_id' => null,
                'match_confidence' => null,
                'reconciliation_notes' => 'Manually unmarked at '.now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Match berhasil dibatalkan',
            ]);

        } catch (AuthorizationException|ModelNotFoundException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Unmark failed: '.$e->getMessage(), [
                'source_id' => $request->source_id,
                'source_table' => $request->source_table,
                'bank_item_id' => $request->bank_item_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan match: '.$e->getMessage(),
            ], 500);
        }
    }

    private function authorizedBankItem(int $id): BankReconciliationItem
    {
        $bankItem = BankReconciliationItem::query()
            ->whereHas('bankStatement')
            ->with('bankStatement')
            ->findOrFail($id);

        Gate::authorize('update', $bankItem->bankStatement);

        return $bankItem;
    }

    private function sourceRecord(string $table, int $id, int $paymentMethodId): Model
    {
        $modelClass = self::SOURCE_MODELS[$table];
        $record = $modelClass::query()->findOrFail($id);

        abort_unless(
            (int) $record->getAttribute('payment_method_id') === $paymentMethodId,
            422,
            'Transaksi dan rekening koran harus menggunakan rekening yang sama.',
        );

        return $record;
    }
}
