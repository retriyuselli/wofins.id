<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SimulasiProduk extends Model
{
    use BelongsToCompany;
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'penawaran_seq',
        'prospect_id',
        'product_id',
        'slug',
        'total_price',
        'user_id',
        'promo',
        'penambahan',
        'pengurangan',
        'grand_total',
        'total_simulation',
        'notes',
        'customer_name',
        'customer_email',
        'customer_phone',
        'payment_dp_amount',
        'payment_term2_amount',
        'payment_term3_amount',
        'payment_term4_amount',
        'payment_simulation',
        'last_edited_by',
        'name_ttd',
        'title_ttd',
        'contract_number',
    ];

    protected $casts = [
        'payment_simulation' => 'array',
    ];

    protected $table = 'simulasi_produks';

    protected static function booted()
    {
        static::creating(function ($model) {
            if (! $model->user_id) {
                $model->user_id = Auth::id();
            }

            if (empty($model->penawaran_seq)) {
                $companyId = $model->company_id ? (int) $model->company_id : null;
                $model->penawaran_seq = static::nextPenawaranSeq($companyId);
            }
        });

        static::updating(function ($model) {
            $model->last_edited_by = Auth::id();
        });
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['prospect_id', 'product_id', 'total_price', 'promo'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('simulasi');
    }

    public function lastEditedBy()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    // public function getGrandTotalAttribute()
    // {
    //     return $this->total_price + $this->penambahan - $this->promo - $this->pengurangan;
    // }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public static function nextPenawaranSeq(?int $companyId): int
    {
        $query = static::withoutGlobalScopes()->withTrashed();

        if ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }

        return (int) $query->max('penawaran_seq') + 1;
    }

    public function getPenawaranNumberAttribute(): string
    {
        $seq = (int) ($this->penawaran_seq ?: $this->id);

        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public static function generateUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $originalSlug = $slug;
        $counter = 1;

        while (self::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
