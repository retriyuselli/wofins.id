<?php

namespace App\Models;

use App\Enums\ProspectAppStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProspectApp extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'company_id',
        'full_name',
        'email',
        'position',
        'phone',
        'company_name',
        'industry_id',
        'name_of_website',
        'service',
        'notes',
        'user_size',
        'harga',
        'bayar',
        'tgl_bayar',
        'reason_for_interest',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'tgl_bayar'    => 'date',
        'harga'        => 'integer',
        'bayar'        => 'integer',
        'status'       => ProspectAppStatus::class,
    ];

    /**
     * Opsi ukuran perusahaan — sumber tunggal untuk front & Filament.
     *
     * @return array<string, string>
     */
    public static function userSizeOptions(?string $current = null): array
    {
        $options = [
            '1-10' => '1-10 karyawan',
            '11-50' => '11-50 karyawan',
            '51-200' => '51-200 karyawan',
            '201-500' => '201-500 karyawan',
            '501-1000' => '501-1000 karyawan',
            '1000+' => 'Lebih dari 1000 karyawan',
        ];

        // Nilai lama dari Filament sebelumnya
        if ($current === '50+' && ! isset($options['50+'])) {
            $options['50+'] = '50+ karyawan (lama)';
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function userSizeKeys(): array
    {
        return array_keys(static::userSizeOptions());
    }

    // Relationships

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'email', 'company_name', 'service', 'user_id'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('prospect_app');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Accessors
    public function getStatusBadgeColorAttribute(): string
    {
        return $this->status->getColor();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->getLabel();
    }

    public function getSisaBayarAttribute(): int
    {
        return max(0, ($this->harga ?? 0) - ($this->bayar ?? 0));
    }
}
