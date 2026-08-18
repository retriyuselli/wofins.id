<?php

namespace App\Models;

use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Employee extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'email',
        'instagram',
        'kontrak',
        'phone',
        'address',
        'position',
        'salary',
        'tunjangan',
        'date_of_birth',
        'date_of_join',
        'date_of_out',
        'no_rek',
        'user_id',
        'bank_name',
        'photo',
        'note',
    ];

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            $base = filled($employee->slug)
                ? (string) $employee->slug
                : (string) ($employee->name ?: 'karyawan');
            $employee->slug = static::generateUniqueSlug($base);

            if (! Schema::hasColumn('employees', 'company_id')) {
                return;
            }

            if ($employee->company_id) {
                return;
            }

            if ($employee->user_id) {
                $fromUser = User::query()->whereKey($employee->user_id)->value('company_id');
                if ($fromUser) {
                    $employee->company_id = $fromUser;

                    return;
                }
            }

            $companyId = UserVisibility::companyId();
            if ($companyId) {
                $employee->company_id = $companyId;
            }
        });

        static::updating(function (Employee $employee): void {
            if (! $employee->isDirty('slug') && ! $employee->isDirty('name')) {
                return;
            }

            $base = filled($employee->slug)
                ? (string) $employee->slug
                : (string) ($employee->name ?: 'karyawan');
            $employee->slug = static::generateUniqueSlug($base, (int) $employee->id);
        });

        static::addGlobalScope('tenant_company', function (Builder $builder) {
            if (! Schema::hasColumn('employees', 'company_id')) {
                return;
            }

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
            ->logOnly(['company_id', 'name', 'email', 'position', 'phone', 'user_id', 'date_of_out'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('employee');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataPribadi(): HasOne
    {
        return $this->hasOne(DataPribadi::class, 'email', 'email');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'karyawan';
        $slug = $base;
        $counter = 1;

        while (
            static::withoutGlobalScopes()
                ->withTrashed()
                ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Cari karyawan dengan nama sama (case-insensitive) di company — untuk warning, bukan blokir.
     *
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    public static function findSameNameInCompany(string $name, ?int $ignoreId = null, ?int $companyId = null): \Illuminate\Support\Collection
    {
        $name = trim($name);
        if ($name === '') {
            return collect();
        }

        $companyId ??= UserVisibility::companyId();

        return static::query()
            ->when(
                $companyId !== null,
                fn (Builder $q) => $q->where('company_id', $companyId),
                fn (Builder $q) => $q->whereNull('company_id'),
            )
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'name', 'email', 'position']);
    }

    public function getEmCountAttribute()
    {
        $totEM = Order::where('employee_id', $this->id)->count();

        return $totEM;
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_join' => 'date',
            'date_of_out' => 'date',
            'salary' => 'integer',
            'tunjangan' => 'integer',
        ];
    }
}
