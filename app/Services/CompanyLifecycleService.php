<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Company;
use App\Models\DataPribadi;
use App\Models\ExpenseOps;
use App\Models\FixedAsset;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\ProspectApp;
use App\Models\SimulasiProduk;
use App\Models\User;
use App\Models\Vendor;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Lifecycle perusahaan:
 * - deactivate: arsipkan (user & data tetap, akses diblokir)
 * - reactivate: aktifkan kembali
 * - purge: soft-delete data operasional + hapus company (super_admin)
 */
class CompanyLifecycleService
{
    public function deactivate(Company $company, ?User $actor = null): Company
    {
        $this->assertSuperAdmin($actor);

        if (! $company->isActive()) {
            return $company;
        }

        $actor ??= Auth::user();

        $company->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => $actor instanceof User ? $actor->id : null,
            'crew_invite_enabled' => false,
        ])->save();

        CompanySubscription::forgetCache($company->id);

        activity('company')
            ->performedOn($company)
            ->causedBy($actor)
            ->withProperties(['action' => 'deactivate'])
            ->log('Company dinonaktifkan (arsip)');

        return $company->refresh();
    }

    public function reactivate(Company $company, ?User $actor = null): Company
    {
        $this->assertSuperAdmin($actor);

        if ($company->isActive()) {
            return $company;
        }

        $actor ??= Auth::user();

        $company->forceFill([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ])->save();

        CompanySubscription::forgetCache($company->id);

        activity('company')
            ->performedOn($company)
            ->causedBy($actor)
            ->withProperties(['action' => 'reactivate'])
            ->log('Company diaktifkan kembali');

        return $company->refresh();
    }

    /**
     * Hapus permanen company + soft-delete data terkait.
     * Wajib konfirmasi nama perusahaan (case-sensitive trim).
     *
     * @return array{users: int, orders: int, vendors: int, products: int, prospects: int, simulasi: int}
     */
    public function purge(Company $company, string $confirmationName, ?User $actor = null): array
    {
        $this->assertSuperAdmin($actor);

        $expected = trim((string) $company->company_name);
        $given = trim($confirmationName);

        if ($expected === '' || $given !== $expected) {
            throw new InvalidArgumentException(
                'Konfirmasi nama perusahaan tidak cocok. Ketik nama persis seperti tercatat.'
            );
        }

        $actor ??= Auth::user();
        $companyId = (int) $company->id;
        $companyName = (string) $company->company_name;

        $stats = [
            'users' => 0,
            'orders' => 0,
            'vendors' => 0,
            'products' => 0,
            'prospects' => 0,
            'simulasi' => 0,
        ];

        try {
            DB::transaction(function () use ($company, $companyId, &$stats): void {
                $userIds = User::query()
                    ->where('company_id', $companyId)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($userIds !== []) {
                    $stats['orders'] = $this->softDeleteByIds(Order::class, 'user_id', $userIds);
                    $stats['prospects'] = $this->softDeleteByIds(Prospect::class, 'user_id', $userIds);
                    $stats['simulasi'] = $this->softDeleteByIds(SimulasiProduk::class, 'user_id', $userIds);
                    $stats['vendors'] = $this->softDeleteByIds(Vendor::class, 'created_by', $userIds);
                    $stats['products'] = $this->softDeleteByIds(Product::class, 'created_by', $userIds);

                    // Expense / DataPembayaran ikut soft-delete lewat Order::deleting

                    // Akun tim: terminate + lepas company (bukan hard delete — hindari FK payroll/ND)
                    $stats['users'] = User::query()
                        ->whereIn('id', $userIds)
                        ->update([
                            'status' => 'terminated',
                            'company_id' => null,
                            'expire_date' => now(),
                        ]);
                }

                $paymentMethodIds = [];
                if (Schema::hasTable('payment_methods') && Schema::hasColumn('payment_methods', 'company_id')) {
                    $paymentMethodIds = PaymentMethod::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }

                if ($paymentMethodIds !== []) {
                    if (Schema::hasTable('expense_ops') && Schema::hasColumn('expense_ops', 'payment_method_id')) {
                        $this->softDeleteByIds(ExpenseOps::class, 'payment_method_id', $paymentMethodIds);
                    }
                    if (Schema::hasTable('pendapatan_lains') && Schema::hasColumn('pendapatan_lains', 'payment_method_id')) {
                        $this->softDeleteByIds(PendapatanLain::class, 'payment_method_id', $paymentMethodIds);
                    }
                    if (Schema::hasTable('pengeluaran_lains') && Schema::hasColumn('pengeluaran_lains', 'payment_method_id')) {
                        $this->softDeleteByIds(PengeluaranLain::class, 'payment_method_id', $paymentMethodIds);
                    }
                }

                if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'company_id')) {
                    Category::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->delete();
                }

                // Rekening dibiarkan; company_id akan SET NULL saat company dihapus (hindari bentrok FK ke transaksi soft-deleted).

                if (Schema::hasTable('fixed_assets') && Schema::hasColumn('fixed_assets', 'company_id')) {
                    FixedAsset::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->get()
                        ->each->delete();
                }

                if (Schema::hasTable('data_pribadis') && Schema::hasColumn('data_pribadis', 'company_id')) {
                    DataPribadi::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->get()
                        ->each->delete();
                }

                if (Schema::hasTable('prospect_apps') && Schema::hasColumn('prospect_apps', 'company_id')) {
                    ProspectApp::query()
                        ->where('company_id', $companyId)
                        ->get()
                        ->each->delete();
                }

                $company->delete();
            });
        } catch (Throwable $e) {
            Log::error('Company purge failed', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Gagal menghapus permanen perusahaan: '.$e->getMessage(),
                previous: $e
            );
        }

        CompanySubscription::forgetCache($companyId);

        activity('company')
            ->causedBy($actor)
            ->withProperties([
                'action' => 'purge',
                'company_id' => $companyId,
                'company_name' => $companyName,
                'stats' => $stats,
            ])
            ->log("Company \"{$companyName}\" dihapus permanen beserta data terkait");

        return $stats;
    }

    /**
     * @param  class-string  $modelClass
     * @param  list<int>  $ids
     */
    private function softDeleteByIds(string $modelClass, string $column, array $ids): int
    {
        if ($ids === [] || ! class_exists($modelClass)) {
            return 0;
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $modelClass;
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $count = 0;

        $modelClass::query()
            ->whereIn($column, $ids)
            ->orderBy($model->getKeyName())
            ->chunkById(100, function ($rows) use (&$count): void {
                foreach ($rows as $row) {
                    $row->delete();
                    $count++;
                }
            });

        return $count;
    }

    private function assertSuperAdmin(?User $actor = null): void
    {
        if ($actor) {
            if (! method_exists($actor, 'hasRole') || ! $actor->hasRole('super_admin')) {
                throw new RuntimeException('Hanya super admin yang boleh mengelola lifecycle perusahaan.');
            }

            return;
        }

        if (! ProFeatures::actorIsSuperAdmin()) {
            throw new RuntimeException('Hanya super admin yang boleh mengelola lifecycle perusahaan.');
        }
    }
}
