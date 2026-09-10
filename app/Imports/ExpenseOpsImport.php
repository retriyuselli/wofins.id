<?php

namespace App\Imports;

use App\Models\ExpenseOps;
use App\Models\PaymentMethod;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Exception;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExpenseOpsImport implements SkipsEmptyRows, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
{
    use Importable, SkipsErrors, SkipsFailures;

    private int $rowCount = 0;

    private int $skippedQuota = 0;

    public function __construct(private PaymentMethod $paymentMethod)
    {
    }

    public function model(array $row): ?ExpenseOps
    {
        if (! $this->paymentMethodBelongsToActorCompany()) {
            return null;
        }

        if (! CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSE_OPS)) {
            $this->skippedQuota++;

            return null;
        }

        $name = $this->cell($row, 'name', 'nama', 'nama_pengeluaran');
        $amount = $this->cell($row, 'amount', 'nominal');
        $date = $this->cell($row, 'date_expense', 'tanggal', 'tanggal_pengeluaran');

        if (blank($name) || $amount === null || $amount === '' || blank($date)) {
            return null;
        }

        $this->rowCount++;

        $companyId = $this->paymentMethod->company_id ?: UserVisibility::companyId();

        return new ExpenseOps([
            'company_id' => $companyId,
            'payment_method_id' => $this->paymentMethod->id,
            'name' => $name,
            'amount' => (int) round($this->transformAmount($amount)),
            'date_expense' => $this->transformDate($date),
            'no_nd' => $this->cell($row, 'no_nd'),
            'note' => $this->cell($row, 'note', 'catatan'),
            'kategori_transaksi' => 'uang_keluar',
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => ['nullable'],
            '*.amount' => ['nullable'],
            '*.date_expense' => ['nullable'],
        ];
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getSkippedQuota(): int
    {
        return $this->skippedQuota;
    }

    private function paymentMethodBelongsToActorCompany(): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return (bool) $this->paymentMethod->company_id;
        }

        $companyId = UserVisibility::companyId();

        return $companyId !== null
            && (int) $this->paymentMethod->company_id === $companyId;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function cell(array $row, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function transformAmount(mixed $amount): float
    {
        if (is_string($amount)) {
            $amount = preg_replace('/[^0-9.-]/', '', $amount);
        }

        return (float) $amount;
    }

    private function transformDate(mixed $date): Carbon
    {
        try {
            if (is_numeric($date)) {
                return Carbon::instance(Date::excelToDateTimeObject($date));
            }

            return Carbon::parse((string) $date);
        } catch (Exception) {
            return Carbon::now();
        }
    }
}
