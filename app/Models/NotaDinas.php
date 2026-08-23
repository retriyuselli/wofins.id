<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\UserVisibility;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NotaDinas extends Model
{
    use BelongsToCompany;
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'no_nd',
        'kategori_nd',
        'tanggal',
        'pengirim_id',      // relasi ke user/admin
        'penerima_id',      // relasi ke user/finance
        'sifat',
        'hal',
        'status',           // draft, diajukan, disetujui, dibayar, ditolak
        'catatan',
        'nd_upload',        // file upload nota dinas
        'approved_by',      // user id yang approve
        'approved_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'approved_at' => 'datetime',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nomor', 'kategori', 'perihal', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('nota_dinas');
    }

    public function details()
    {
        return $this->hasMany(NotaDinasDetail::class, 'nota_dinas_id');
    }

    public function pengirim()
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function penerima()
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getFormattedLabelAttribute()
    {
        return "ND-{$this->no_nd} - {$this->hal}";
    }

    /**
     * Generate nomor nota dinas otomatis berdasarkan kategori, tahun, dan company.
     * Format baru: ND/[INISIAL_WO]/[KATEGORI]/[NOMOR_URUT]/[TAHUN]
     * Format lama (masih dihitung urutannya): ND/[KATEGORI]/[NOMOR_URUT]/[TAHUN]
     * Urutan unik per company — bukan global platform.
     */
    public static function generateNomorND($kategori = 'BIS', $tahun = null, ?int $companyId = null)
    {
        if (! $tahun) {
            $tahun = date('Y');
        }

        $validKategori = ['BIS', 'OPS', 'LAIN'];
        if (! in_array(strtoupper($kategori), $validKategori)) {
            $kategori = 'BIS';
        }

        $kategori = strtoupper($kategori);
        $companyId ??= UserVisibility::companyId();
        $inisial = static::woInitialFor($companyId);

        $query = self::withoutGlobalScope('tenant_company')
            ->withTrashed()
            ->where(function ($q) use ($inisial, $kategori, $tahun) {
                $q->where('no_nd', 'like', "ND/{$inisial}/{$kategori}/%/{$tahun}")
                    ->orWhere('no_nd', 'like', "ND/{$kategori}/%/{$tahun}");
            });

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        } else {
            $query->whereNull('company_id');
        }

        $maxSeq = 0;
        foreach ($query->get(['no_nd']) as $row) {
            $seq = static::seqFromNoNd((string) $row->no_nd, $kategori, (string) $tahun);
            if ($seq !== null) {
                $maxSeq = max($maxSeq, $seq);
            }
        }

        $formattedNumber = str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);

        return "ND/{$inisial}/{$kategori}/{$formattedNumber}/{$tahun}";
    }

    public static function woInitialFor(?int $companyId): string
    {
        if ($companyId) {
            $company = Company::query()->find($companyId);
            if ($company) {
                return $company->woInitial();
            }
        }

        return 'MW';
    }

    /**
     * Nomor urut dari ND/INISIAL/KATEGORI/SEQ/TAHUN atau ND/KATEGORI/SEQ/TAHUN.
     */
    public static function seqFromNoNd(string $noNd, string $kategori, string $tahun): ?int
    {
        $parts = explode('/', $noNd);
        $kategori = strtoupper($kategori);

        if (count($parts) === 5
            && strtoupper((string) ($parts[2] ?? '')) === $kategori
            && (string) ($parts[4] ?? '') === $tahun) {
            return (int) $parts[3];
        }

        if (count($parts) === 4
            && strtoupper((string) ($parts[1] ?? '')) === $kategori
            && (string) ($parts[3] ?? '') === $tahun) {
            return (int) $parts[2];
        }

        return null;
    }

    /**
     * Get available categories for nota dinas
     */
    public static function getKategoriOptions()
    {
        return [
            'BIS' => 'Bisnis',
            'OPS' => 'Operasional',
            'LAIN' => 'Lain-lain',
        ];
    }

    /**
     * Override delete to prevent deletion if has related details
     */
    public function delete()
    {
        // Check if NotaDinas has related details
        $detailCount = $this->details()->count();

        if ($detailCount > 0) {
            throw new Exception("Cannot delete Nota Dinas '{$this->no_nd}' because it has {$detailCount} related detail record(s). Please remove all details first.");
        }

        return parent::delete();
    }

    /**
     * Force delete with cascade deletion of all related records
     * Use with extreme caution - this will permanently delete all related data
     */
    public function forceDelete()
    {
        try {
            // Start database transaction
            DB::beginTransaction();

            // Store record info for logging
            $recordId = $this->id;
            $recordNo = $this->no_nd;

            // Force delete all related details first
            // Get all details (including soft deleted ones) and force delete them individually
            $details = $this->details()->withTrashed()->get();
            $detailsDeleted = 0;

            foreach ($details as $detail) {
                $detail->forceDelete();
                $detailsDeleted++;
            }

            // Then force delete the NotaDinas itself using raw query to ensure it works
            $result = $this->newQuery()->where('id', $recordId)->forceDelete();

            DB::commit();

            // Log the successful deletion
            Log::info("Force deleted NotaDinas {$recordNo} (ID: {$recordId}) with {$detailsDeleted} details");

            return $result;

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to force delete NotaDinas: '.$e->getMessage());
            throw new Exception('Failed to force delete Nota Dinas: '.$e->getMessage());
        }
    }
}
